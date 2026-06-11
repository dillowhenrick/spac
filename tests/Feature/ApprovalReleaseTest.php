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
use App\Notifications\ResponseReleasedNotification;
use Illuminate\Support\Facades\Notification;

function approvalManagerUser(Institution $institution): User
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

function approvalStaffUser(Institution $institution): User
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

function pendingApprovalRequest(Institution $institution, User $manager, User $staff): AppRequest
{
    $agency = Agency::factory()->create();
    $requester = User::factory()->create();
    Membership::factory()->create([
        'user_id' => $requester->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    $request = AppRequest::factory()->create([
        'requester_id' => $requester->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
        'status' => RequestStatus::PendingManagerReview,
        'submitted_at' => now()->subDays(2),
    ]);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff->id,
        'is_self_assigned' => false,
        'assigned_at' => now()->subDay(),
    ]);

    RequestResponse::create([
        'request_id' => $request->id,
        'drafted_by' => $staff->id,
        'status' => ResponseStatus::PendingApproval,
        'notes' => 'Staff notes',
        'submitted_at' => now()->subHour(),
    ]);

    return $request;
}

// ── Approve Response ──────────────────────────────────────────────────────────

test('manager can approve pending response', function () {
    Notification::fake();

    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/approve-response")
        ->assertRedirect("/institution/requests/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::Approved);
    expect($request->fresh()->response->status)->toBe(ResponseStatus::Approved);
});

test('non-manager cannot approve response', function () {
    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);

    $this->actingAs($staff)
        ->post("/institution/requests/{$request->id}/approve-response")
        ->assertForbidden();
});

test('cannot approve already approved response', function () {
    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);
    $request->update(['status' => RequestStatus::Approved]);
    $request->response()->update(['status' => ResponseStatus::Approved]);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/approve-response")
        ->assertForbidden();
});

// ── Reject Response ───────────────────────────────────────────────────────────

test('manager can reject response with notes', function () {
    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/reject-response", [
            'notes' => 'Please include the account closure date.',
        ])
        ->assertRedirect("/institution/requests/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::RevisionRequested);
    expect($request->fresh()->response->status)->toBe(ResponseStatus::Rejected);
    expect($request->fresh()->response->notes)->toBe('Please include the account closure date.');
});

test('reject requires notes', function () {
    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/reject-response", ['notes' => ''])
        ->assertSessionHasErrors('notes');
});

// ── Release Response ──────────────────────────────────────────────────────────

test('manager can release approved response', function () {
    Notification::fake();

    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);
    $request->update(['status' => RequestStatus::Approved]);
    $request->response()->update(['status' => ResponseStatus::Approved]);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/release-response")
        ->assertRedirect("/institution/requests/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::Released);
    expect($request->fresh()->response->status)->toBe(ResponseStatus::Released);

    Notification::assertSentTo($request->requester, ResponseReleasedNotification::class);
});

test('cannot release pending approval response', function () {
    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/release-response")
        ->assertForbidden();
});

// ── Revision Cycle ────────────────────────────────────────────────────────────

test('staff can re-draft after rejection', function () {
    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);
    $request->update(['status' => RequestStatus::RevisionRequested]);
    $request->response()->update(['status' => ResponseStatus::Rejected]);

    $this->actingAs($staff)
        ->post("/institution/assigned/{$request->id}/response", [
            'notes' => 'Revised notes with account closure date included.',
        ])
        ->assertRedirect("/institution/assigned/{$request->id}");

    expect($request->fresh()->response->status)->toBe(ResponseStatus::Draft);
    expect($request->fresh()->response->notes)->toBe('Revised notes with account closure date included.');
});

// ── Requester Sees Released Response ─────────────────────────────────────────

test('requester sees released response on show page', function () {
    $institution = Institution::factory()->create();
    $manager = approvalManagerUser($institution);
    $staff = approvalStaffUser($institution);
    $request = pendingApprovalRequest($institution, $manager, $staff);
    $request->update(['status' => RequestStatus::Released]);
    $request->response()->update(['status' => ResponseStatus::Released]);

    $this->actingAs($request->requester)
        ->get("/requests/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('request.status', 'released')
            ->whereNot('request.response', null)
        );
});
