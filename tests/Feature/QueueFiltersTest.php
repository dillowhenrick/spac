<?php

use App\Enums\RequestStatus;
use App\Models\Institution;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;

// ── Verification Queue ────────────────────────────────────────────

test('verification queue returns all pending without filters', function () {
    $verifier = verifierUser();

    AppRequest::factory()->count(2)->submitted()->create();
    AppRequest::factory()->create(['status' => RequestStatus::UnderVerification, 'submitted_at' => now()]);

    $this->actingAs($verifier)
        ->get(route('verification.index'))
        ->assertInertia(fn ($page) => $page
            ->component('verification/index')
            ->has('requests', 3)
        );
});

test('verification queue filters by status', function () {
    $verifier = verifierUser();

    AppRequest::factory()->count(2)->submitted()->create();
    AppRequest::factory()->create(['status' => RequestStatus::UnderVerification, 'submitted_at' => now()]);

    $this->actingAs($verifier)
        ->get(route('verification.index', ['status' => 'submitted']))
        ->assertInertia(fn ($page) => $page->has('requests', 2));
});

test('verification queue filters by date_from', function () {
    $verifier = verifierUser();

    AppRequest::factory()->submitted()->create(['submitted_at' => now()->subDays(5)]);
    AppRequest::factory()->submitted()->create(['submitted_at' => now()]);

    $this->actingAs($verifier)
        ->get(route('verification.index', ['date_from' => now()->toDateString()]))
        ->assertInertia(fn ($page) => $page->has('requests', 1));
});

test('verification queue filters by date_to', function () {
    $verifier = verifierUser();

    AppRequest::factory()->submitted()->create(['submitted_at' => now()->subDays(5)]);
    AppRequest::factory()->submitted()->create(['submitted_at' => now()]);

    $this->actingAs($verifier)
        ->get(route('verification.index', ['date_to' => now()->subDays(3)->toDateString()]))
        ->assertInertia(fn ($page) => $page->has('requests', 1));
});

test('verification queue passes filters back to page', function () {
    $verifier = verifierUser();

    $this->actingAs($verifier)
        ->get(route('verification.index', ['status' => 'submitted', 'date_from' => '2026-01-01']))
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'submitted')
            ->where('filters.date_from', '2026-01-01')
        );
});

// ── Institution Queue ─────────────────────────────────────────────

test('institution queue filters by status', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);

    AppRequest::factory()->create(['target_institution_id' => $institution->id, 'status' => RequestStatus::Routed, 'submitted_at' => now()]);
    AppRequest::factory()->create(['target_institution_id' => $institution->id, 'status' => RequestStatus::Assigned, 'submitted_at' => now()]);

    $this->actingAs($manager)
        ->get(route('institution.queue', ['status' => 'routed']))
        ->assertInertia(fn ($page) => $page->has('requests', 1));
});

test('institution queue filters by date range', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);

    AppRequest::factory()->create(['target_institution_id' => $institution->id, 'status' => RequestStatus::Routed, 'submitted_at' => now()->subDays(10)]);
    AppRequest::factory()->create(['target_institution_id' => $institution->id, 'status' => RequestStatus::Routed, 'submitted_at' => now()]);

    $this->actingAs($manager)
        ->get(route('institution.queue', ['date_from' => now()->subDays(1)->toDateString()]))
        ->assertInertia(fn ($page) => $page->has('requests', 1));
});

// ── Requester Requests ────────────────────────────────────────────

test('requester request index filters by status', function () {
    $user = dashboardRequesterUser();
    $agencyId = $user->agencyMemberships()->value('organization_id');

    AppRequest::factory()->create(['requester_id' => $user->id, 'agency_id' => $agencyId, 'status' => RequestStatus::Draft]);
    AppRequest::factory()->submitted()->create(['requester_id' => $user->id, 'agency_id' => $agencyId]);

    $this->actingAs($user)
        ->get(route('requests.index', ['status' => 'submitted']))
        ->assertInertia(fn ($page) => $page->has('requests', 1));
});

test('requester request index filters by date range', function () {
    $user = dashboardRequesterUser();
    $agencyId = $user->agencyMemberships()->value('organization_id');

    AppRequest::factory()->submitted()->create(['requester_id' => $user->id, 'agency_id' => $agencyId, 'submitted_at' => now()->subDays(10)]);
    AppRequest::factory()->submitted()->create(['requester_id' => $user->id, 'agency_id' => $agencyId, 'submitted_at' => now()]);

    $this->actingAs($user)
        ->get(route('requests.index', ['date_from' => now()->subDays(1)->toDateString()]))
        ->assertInertia(fn ($page) => $page->has('requests', 1));
});

test('requester request index passes filters back to page', function () {
    $user = dashboardRequesterUser();

    $this->actingAs($user)
        ->get(route('requests.index', ['status' => 'submitted', 'date_from' => '2026-01-01']))
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'submitted')
            ->where('filters.date_from', '2026-01-01')
        );
});

// ── Staff Assignments ─────────────────────────────────────────────

test('staff assignment index filters by status', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);

    $assignedRequest = routedRequest($institution);
    $assignedRequest->update(['status' => RequestStatus::Assigned]);
    RequestAssignment::factory()->create([
        'request_id' => $assignedRequest->id,
        'assigned_to' => $staff->id,
        'assigned_by' => $staff->id,
        'is_self_assigned' => true,
        'assigned_at' => now(),
    ]);

    $inProgressRequest = routedRequest($institution);
    $inProgressRequest->update(['status' => RequestStatus::InProgress]);
    RequestAssignment::factory()->create([
        'request_id' => $inProgressRequest->id,
        'assigned_to' => $staff->id,
        'assigned_by' => $staff->id,
        'is_self_assigned' => true,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->get(route('institution.assigned', ['status' => 'assigned']))
        ->assertInertia(fn ($page) => $page->has('assignments', 1));
});

test('staff assignment index passes filters back to page', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);

    $this->actingAs($staff)
        ->get(route('institution.assigned', ['status' => 'assigned', 'date_from' => '2026-01-01']))
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'assigned')
            ->where('filters.date_from', '2026-01-01')
        );
});

test('institution queue only shows own institution requests', function () {
    $institutionA = Institution::factory()->create();
    $institutionB = Institution::factory()->create();
    $manager = managerUser($institutionA);

    AppRequest::factory()->create(['target_institution_id' => $institutionA->id, 'status' => RequestStatus::Routed, 'submitted_at' => now()]);
    AppRequest::factory()->create(['target_institution_id' => $institutionB->id, 'status' => RequestStatus::Routed, 'submitted_at' => now()]);

    $this->actingAs($manager)
        ->get(route('institution.queue'))
        ->assertInertia(fn ($page) => $page->has('requests', 1));
});
