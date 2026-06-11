<?php

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use App\Models\User;

function dashboardRequesterUser(): User
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

// ── Requester ─────────────────────────────────────────────────────

test('dashboard shows requester stats', function () {
    $user = dashboardRequesterUser();

    AppRequest::factory()->count(2)->submitted()->create(['requester_id' => $user->id, 'agency_id' => $user->agencyMemberships()->value('organization_id')]);
    AppRequest::factory()->create(['requester_id' => $user->id, 'agency_id' => $user->agencyMemberships()->value('organization_id'), 'status' => RequestStatus::Released, 'submitted_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('stats.requester.total', 3)
            ->where('stats.requester.submitted', 2)
            ->where('stats.requester.released', 1)
        );
});

test('dashboard does not include other requesters requests', function () {
    $user = dashboardRequesterUser();
    $other = requesterUser();

    AppRequest::factory()->count(3)->submitted()->create(['requester_id' => $other->id, 'agency_id' => $other->agencyMemberships()->value('organization_id')]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.requester.total', 0));
});

// ── Verifier ──────────────────────────────────────────────────────

test('dashboard shows verifier pending count', function () {
    $verifier = verifierUser();

    AppRequest::factory()->count(3)->submitted()->create();
    AppRequest::factory()->create(['status' => RequestStatus::UnderVerification, 'submitted_at' => now()]);

    $this->actingAs($verifier)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.verifier.pending', 4));
});

test('dashboard shows overdue returns for verifier', function () {
    $verifier = verifierUser();

    AppRequest::factory()->create([
        'status' => RequestStatus::InProgress,
        'return_due_at' => now()->subHours(1),
        'submitted_at' => now(),
    ]);
    AppRequest::factory()->create([
        'status' => RequestStatus::InProgress,
        'return_due_at' => now()->addHours(10),
        'submitted_at' => now(),
    ]);

    $this->actingAs($verifier)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.verifier.overdue_returns', 1));
});

// ── Manager ───────────────────────────────────────────────────────

test('dashboard shows manager queue and pending review counts', function () {
    $institution = Institution::factory()->create();
    $manager = managerUser($institution);

    AppRequest::factory()->count(2)->create(['target_institution_id' => $institution->id, 'status' => RequestStatus::Routed, 'submitted_at' => now()]);
    AppRequest::factory()->create(['target_institution_id' => $institution->id, 'status' => RequestStatus::PendingManagerReview, 'submitted_at' => now()]);

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('stats.manager.queue', 2)
            ->where('stats.manager.pending_review', 1)
        );
});

// ── Staff ─────────────────────────────────────────────────────────

test('dashboard shows staff assigned count', function () {
    $institution = Institution::factory()->create();
    $staff = staffUser($institution);

    $request = routedRequest($institution);
    $request->update(['status' => RequestStatus::Assigned]);

    RequestAssignment::factory()->create([
        'request_id' => $request->id,
        'assigned_to' => $staff->id,
        'assigned_by' => $staff->id,
        'is_self_assigned' => true,
        'assigned_at' => now(),
    ]);

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('stats.staff.assigned', 1));
});

// ── Role isolation ────────────────────────────────────────────────

test('dashboard stats key is absent when user lacks that role', function () {
    $verifier = verifierUser();

    $this->actingAs($verifier)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->missing('stats.requester')
            ->missing('stats.manager')
            ->missing('stats.staff')
            ->has('stats.verifier')
        );
});
