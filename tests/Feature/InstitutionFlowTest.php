<?php

use App\Enums\RequestStatus;
use App\Enums\ResponseStatus;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use App\Models\RequestResponse;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function managerUser(Institution $institution): User
{
    $user = User::factory()->create();
    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'institution',
        'organization_id' => $institution->id,
        'role' => Role::InstitutionManager,
        'is_primary' => true,
    ]);

    return $user;
}

function staffUser(Institution $institution): User
{
    $user = User::factory()->create();
    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'institution',
        'organization_id' => $institution->id,
        'role' => Role::InstitutionStaff,
        'is_primary' => true,
    ]);

    return $user;
}

function routedRequest(Institution $institution): AppRequest
{
    $agency = Agency::factory()->create();
    $requester = User::factory()->create();
    Membership::factory()->create([
        'user_id' => $requester->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    return AppRequest::factory()->create([
        'requester_id' => $requester->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
        'status' => RequestStatus::Routed,
        'submitted_at' => now(),
    ]);
}

// ── Manager Queue ─────────────────────────────────────────────────────────────

test('guest redirected from institution queue', function () {
    $this->get('/institution/queue')->assertRedirect('/login');
});

test('manager can access institution queue', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);

    $this->actingAs($manager)
        ->get('/institution/queue')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('institution/queue'));
});

test('non-manager cannot access institution queue', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/institution/queue')
        ->assertForbidden();
});

test('queue shows only this institution requests', function () {
    $institutionA = Institution::factory()->create();
    $institutionB = Institution::factory()->create();
    $manager = managerUser($institutionA);
    $requestA = routedRequest($institutionA);
    routedRequest($institutionB);

    $this->actingAs($manager)
        ->get('/institution/queue')
        ->assertInertia(fn ($page) => $page
            ->has('requests', 1)
            ->where('requests.0.id', $requestA->id)
        );
});

// ── Manager Show ──────────────────────────────────────────────────────────────

test('manager can view request detail', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);
    $request = routedRequest($institution);

    $this->actingAs($manager)
        ->get("/institution/requests/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/show')
            ->where('request.id', $request->id)
            ->where('request.can.assign', true)
        );
});

test('manager of different institution cannot view request', function () {
    $institutionA = Institution::factory()->create();
    $institutionB = Institution::factory()->create();
    $manager = managerUser($institutionA);
    $request = routedRequest($institutionB);

    $this->actingAs($manager)
        ->get("/institution/requests/{$request->id}")
        ->assertForbidden();
});

// ── Assign Self ───────────────────────────────────────────────────────────────

test('manager can assign request to self', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);
    $request = routedRequest($institution);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/assign-self")
        ->assertRedirect("/institution/requests/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::Assigned);

    $assignment = RequestAssignment::where('request_id', $request->id)->first();
    expect($assignment)->not->toBeNull()
        ->and($assignment->assigned_to)->toBe($manager->id)
        ->and($assignment->is_self_assigned)->toBeTrue();
});

// ── Assign Staff ──────────────────────────────────────────────────────────────

test('manager can assign request to staff', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);
    $staff1 = staffUser($institution);
    $staff2 = staffUser($institution);
    $request = routedRequest($institution);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/assign", [
            'staff_ids' => [$staff1->id, $staff2->id],
        ])
        ->assertRedirect("/institution/requests/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::Assigned);
    expect(RequestAssignment::where('request_id', $request->id)->count())->toBe(2);
});

test('assign replaces previous assignments', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);
    $staff1 = staffUser($institution);
    $staff2 = staffUser($institution);
    $request = routedRequest($institution);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff1->id,
        'is_self_assigned' => false,
        'assigned_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/assign", [
            'staff_ids' => [$staff2->id],
        ]);

    expect(RequestAssignment::where('request_id', $request->id)->count())->toBe(1)
        ->and(RequestAssignment::where('request_id', $request->id)->value('assigned_to'))->toBe($staff2->id);
});

test('assign requires staff_ids', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);
    $request = routedRequest($institution);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/assign", [])
        ->assertSessionHasErrors('staff_ids');
});

// ── Staff Assigned List ───────────────────────────────────────────────────────

test('staff can access assigned list', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);

    $this->actingAs($staff)
        ->get('/institution/assigned')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('institution/assigned'));
});

test('assigned list shows only staff assignments', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $manager = managerUser($institution);
    $request = routedRequest($institution);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff->id,
        'is_self_assigned' => false,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->get('/institution/assigned')
        ->assertInertia(fn ($page) => $page->has('assignments', 1));
});

// ── Staff Work Area ───────────────────────────────────────────────────────────

test('staff can view assigned request work area', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $manager = managerUser($institution);
    $request = routedRequest($institution);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff->id,
        'is_self_assigned' => false,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->get("/institution/assigned/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('institution/work-area')
            ->where('request.can.draft_response', true)
        );
});

test('unassigned staff cannot view work area', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $request = routedRequest($institution);

    $this->actingAs($staff)
        ->get("/institution/assigned/{$request->id}")
        ->assertForbidden();
});

// ── Store Response ────────────────────────────────────────────────────────────

test('staff can save response draft', function () {
    Storage::fake('local');

    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $manager = managerUser($institution);
    $request = routedRequest($institution);
    $request->update(['status' => RequestStatus::Assigned]);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff->id,
        'is_self_assigned' => false,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->post("/institution/assigned/{$request->id}/response", [
            'notes' => 'Internal notes for manager.',
            'attachments' => [UploadedFile::fake()->create('response.pdf', 100, 'application/pdf')],
        ])
        ->assertRedirect("/institution/assigned/{$request->id}");

    $response = RequestResponse::where('request_id', $request->id)->first();

    expect($response)->not->toBeNull()
        ->and($response->status)->toBe(ResponseStatus::Draft)
        ->and($response->notes)->toBe('Internal notes for manager.')
        ->and($response->attachments)->toHaveCount(1);

    expect($request->fresh()->status)->toBe(RequestStatus::InProgress);
});

// ── Submit Response ───────────────────────────────────────────────────────────

test('staff can submit response to manager', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $manager = managerUser($institution);
    $request = routedRequest($institution);
    $request->update(['status' => RequestStatus::InProgress]);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff->id,
        'is_self_assigned' => false,
        'assigned_at' => now(),
    ]);

    RequestResponse::create([
        'request_id' => $request->id,
        'drafted_by' => $staff->id,
        'status' => ResponseStatus::Draft,
        'notes' => 'Notes',
    ]);

    $this->actingAs($staff)
        ->post("/institution/assigned/{$request->id}/response/submit")
        ->assertRedirect("/institution/assigned/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::PendingManagerReview);
    expect($request->fresh()->response->status)->toBe(ResponseStatus::PendingApproval);
    expect($request->fresh()->response->submitted_at)->not->toBeNull();
});

test('staff cannot submit without a draft response', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);
    $manager = managerUser($institution);
    $request = routedRequest($institution);
    $request->update(['status' => RequestStatus::InProgress]);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff->id,
        'is_self_assigned' => false,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->post("/institution/assigned/{$request->id}/response/submit")
        ->assertForbidden();
});
