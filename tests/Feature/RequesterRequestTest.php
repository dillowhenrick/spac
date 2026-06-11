<?php

use App\Enums\LegalProcess;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function requesterUser(): User
{
    $user = User::factory()->create();
    $agency = Agency::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
        'is_primary' => true,
    ]);

    return $user;
}

// ── Index ─────────────────────────────────────────────────────────────────────

test('guests are redirected from requests index', function () {
    $this->get('/requests')->assertRedirect('/login');
});

test('requester can view their requests index', function () {
    $user = requesterUser();

    $this->actingAs($user)
        ->get('/requests')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('requests/index'));
});

test('non-requester cannot access requests index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/requests')
        ->assertForbidden();
});

test('requester only sees own requests on index', function () {
    $user = requesterUser();
    $other = requesterUser();
    $institution = Institution::factory()->create();

    $ownRequest = AppRequest::factory()->create([
        'requester_id' => $user->id,
        'agency_id' => $user->agencyMemberships()->first()->organization_id,
        'target_institution_id' => $institution->id,
    ]);

    AppRequest::factory()->create([
        'requester_id' => $other->id,
        'agency_id' => $other->agencyMemberships()->first()->organization_id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->get('/requests')
        ->assertInertia(fn ($page) => $page
            ->has('requests', 1)
            ->where('requests.0.id', $ownRequest->id)
        );
});

// ── Create ────────────────────────────────────────────────────────────────────

test('requester can access create page', function () {
    $user = requesterUser();

    $this->actingAs($user)
        ->get('/requests/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('requests/create')
            ->has('institutions')
            ->has('legalProcesses')
        );
});

test('non-requester cannot access create page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/requests/create')
        ->assertForbidden();
});

// ── Store ─────────────────────────────────────────────────────────────────────

test('requester can store a draft request', function () {
    $user = requesterUser();
    $institution = Institution::factory()->create();

    $this->actingAs($user)
        ->post('/requests', [
            'legal_process' => LegalProcess::Wdcd->value,
            'reference_number' => 'WDCD-2026-0001',
            'target_institution_id' => $institution->id,
        ])
        ->assertRedirect();

    $request = AppRequest::where('requester_id', $user->id)->first();

    expect($request)->not->toBeNull()
        ->and($request->status)->toBe(RequestStatus::Draft)
        ->and($request->submitted_at)->toBeNull();
});

test('store validates required fields', function () {
    $user = requesterUser();

    $this->actingAs($user)
        ->post('/requests', [])
        ->assertSessionHasErrors(['legal_process', 'reference_number', 'target_institution_id']);
});

test('store rejects invalid institution', function () {
    $user = requesterUser();

    $this->actingAs($user)
        ->post('/requests', [
            'legal_process' => LegalProcess::Wdcd->value,
            'reference_number' => 'WDCD-2026-0001',
            'target_institution_id' => 99999,
        ])
        ->assertSessionHasErrors('target_institution_id');
});

test('store handles file attachments', function () {
    Storage::fake('local');

    $user = requesterUser();
    $institution = Institution::factory()->create();

    $this->actingAs($user)
        ->post('/requests', [
            'legal_process' => LegalProcess::Wdcd->value,
            'reference_number' => 'WDCD-2026-0002',
            'target_institution_id' => $institution->id,
            'attachments' => [
                UploadedFile::fake()->create('warrant.pdf', 100, 'application/pdf'),
            ],
        ]);

    $request = AppRequest::where('requester_id', $user->id)->first();

    expect($request->attachments)->toHaveCount(1)
        ->and($request->attachments->first()->original_name)->toBe('warrant.pdf');
});

test('store rejects invalid file types', function () {
    $user = requesterUser();
    $institution = Institution::factory()->create();

    $this->actingAs($user)
        ->post('/requests', [
            'legal_process' => LegalProcess::Wdcd->value,
            'reference_number' => 'WDCD-2026-0003',
            'target_institution_id' => $institution->id,
            'attachments' => [
                UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream'),
            ],
        ])
        ->assertSessionHasErrors('attachments.0');
});

// ── Show ──────────────────────────────────────────────────────────────────────

test('requester can view own request', function () {
    $user = requesterUser();
    $institution = Institution::factory()->create();

    $request = AppRequest::factory()->create([
        'requester_id' => $user->id,
        'agency_id' => $user->agencyMemberships()->first()->organization_id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->get("/requests/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('requests/show')
            ->where('request.id', $request->id)
        );
});

test('requester cannot view another users request', function () {
    $user = requesterUser();
    $other = requesterUser();
    $institution = Institution::factory()->create();

    $request = AppRequest::factory()->create([
        'requester_id' => $other->id,
        'agency_id' => $other->agencyMemberships()->first()->organization_id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->get("/requests/{$request->id}")
        ->assertForbidden();
});

// ── Submit ────────────────────────────────────────────────────────────────────

test('requester can submit a draft request', function () {
    $user = requesterUser();
    $institution = Institution::factory()->create();

    $request = AppRequest::factory()->draft()->create([
        'requester_id' => $user->id,
        'agency_id' => $user->agencyMemberships()->first()->organization_id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->post("/requests/{$request->id}/submit")
        ->assertRedirect("/requests/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::Submitted)
        ->and($request->fresh()->submitted_at)->not->toBeNull();
});

test('requester cannot submit an already submitted request', function () {
    $user = requesterUser();
    $institution = Institution::factory()->create();

    $request = AppRequest::factory()->submitted()->create([
        'requester_id' => $user->id,
        'agency_id' => $user->agencyMemberships()->first()->organization_id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->post("/requests/{$request->id}/submit")
        ->assertForbidden();
});

test('requester cannot submit another users request', function () {
    $user = requesterUser();
    $other = requesterUser();
    $institution = Institution::factory()->create();

    $request = AppRequest::factory()->draft()->create([
        'requester_id' => $other->id,
        'agency_id' => $other->agencyMemberships()->first()->organization_id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->post("/requests/{$request->id}/submit")
        ->assertForbidden();
});
