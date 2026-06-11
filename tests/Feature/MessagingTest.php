<?php

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Models\RequestAssignment;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Notification;

function messagingRequest(): array
{
    $agency = Agency::factory()->create();
    $institution = Institution::factory()->create();

    $requester = User::factory()->create();
    Membership::factory()->create([
        'user_id' => $requester->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    $manager = User::factory()->create();
    Membership::factory()->create([
        'user_id' => $manager->id,
        'organization_type' => 'institution',
        'organization_id' => $institution->id,
        'role' => Role::InstitutionManager,
        'is_primary' => true,
    ]);

    $staff = User::factory()->create();
    Membership::factory()->create([
        'user_id' => $staff->id,
        'organization_type' => 'institution',
        'organization_id' => $institution->id,
        'role' => Role::InstitutionStaff,
    ]);

    $request = AppRequest::factory()->create([
        'requester_id' => $requester->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
        'status' => RequestStatus::Assigned,
        'submitted_at' => now()->subDay(),
    ]);

    RequestAssignment::create([
        'request_id' => $request->id,
        'assigned_by' => $manager->id,
        'assigned_to' => $staff->id,
        'is_self_assigned' => false,
        'assigned_at' => now(),
    ]);

    return compact('request', 'requester', 'manager', 'staff');
}

// ── Requester Messaging ───────────────────────────────────────────────────────

test('requester can send message on own request', function () {
    Notification::fake();
    ['request' => $request, 'requester' => $requester] = messagingRequest();

    $this->actingAs($requester)
        ->post("/requests/{$request->id}/messages", ['body' => 'Please clarify the scope.'])
        ->assertRedirect();

    expect($request->messages()->count())->toBe(1)
        ->and($request->messages()->first()->body)->toBe('Please clarify the scope.');
});

test('requester cannot message on another request', function () {
    ['request' => $request] = messagingRequest();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post("/requests/{$request->id}/messages", ['body' => 'test'])
        ->assertForbidden();
});

test('message requires body', function () {
    ['request' => $request, 'requester' => $requester] = messagingRequest();

    $this->actingAs($requester)
        ->post("/requests/{$request->id}/messages", ['body' => ''])
        ->assertSessionHasErrors('body');
});

// ── Manager Messaging ─────────────────────────────────────────────────────────

test('manager can send message on institution request', function () {
    Notification::fake();
    ['request' => $request, 'manager' => $manager] = messagingRequest();

    $this->actingAs($manager)
        ->post("/institution/requests/{$request->id}/messages", ['body' => 'Please provide account opening date.'])
        ->assertRedirect();

    expect($request->messages()->count())->toBe(1);
});

// ── Staff Messaging ───────────────────────────────────────────────────────────

test('assigned staff can send message', function () {
    Notification::fake();
    ['request' => $request, 'staff' => $staff] = messagingRequest();

    $this->actingAs($staff)
        ->post("/institution/assigned/{$request->id}/messages", ['body' => 'Working on it, ETA tomorrow.'])
        ->assertRedirect();

    expect($request->messages()->count())->toBe(1);
});

test('unassigned user cannot send message via staff route', function () {
    ['request' => $request] = messagingRequest();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post("/institution/assigned/{$request->id}/messages", ['body' => 'test'])
        ->assertForbidden();
});

// ── Notification Dispatch ─────────────────────────────────────────────────────

test('message notifies other participants', function () {
    Notification::fake();
    ['request' => $request, 'requester' => $requester, 'manager' => $manager, 'staff' => $staff] = messagingRequest();

    $this->actingAs($requester)
        ->post("/requests/{$request->id}/messages", ['body' => 'Any update?']);

    Notification::assertSentTo($manager, NewMessageNotification::class);
    Notification::assertSentTo($staff, NewMessageNotification::class);
    Notification::assertNotSentTo($requester, NewMessageNotification::class);
});
