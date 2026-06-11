<?php

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Enums\VerificationDecision;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Models\User;

function verifierUser(): User
{
    $user = User::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => Agency::factory()->create()->id,
        'role' => Role::AmlakasVerifier,
        'is_primary' => true,
    ]);

    return $user;
}

function submittedRequest(): AppRequest
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

    return AppRequest::factory()->submitted()->create([
        'requester_id' => $requester->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
    ]);
}

// ── Queue ─────────────────────────────────────────────────────────────────────

test('guests are redirected from verification queue', function () {
    $this->get('/verification')->assertRedirect('/login');
});

test('verifier can access verification queue', function () {
    $verifier = verifierUser();

    $this->actingAs($verifier)
        ->get('/verification')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('verification/index'));
});

test('non-verifier cannot access verification queue', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/verification')
        ->assertForbidden();
});

test('queue shows submitted requests', function () {
    $verifier = verifierUser();
    $request = submittedRequest();

    $this->actingAs($verifier)
        ->get('/verification')
        ->assertInertia(fn ($page) => $page
            ->has('requests', 1)
            ->where('requests.0.id', $request->id)
        );
});

test('queue does not show draft requests', function () {
    $verifier = verifierUser();
    $agency = Agency::factory()->create();
    $institution = Institution::factory()->create();

    AppRequest::factory()->draft()->create([
        'requester_id' => User::factory()->create()->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($verifier)
        ->get('/verification')
        ->assertInertia(fn ($page) => $page->has('requests', 0));
});

// ── Show ──────────────────────────────────────────────────────────────────────

test('verifier can view request detail', function () {
    $verifier = verifierUser();
    $request = submittedRequest();

    $this->actingAs($verifier)
        ->get("/verification/{$request->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('verification/show')
            ->where('request.id', $request->id)
            ->where('request.can.approve', true)
            ->where('request.can.reject', true)
            ->where('request.can.route', false)
        );
});

test('requester cannot access verification show', function () {
    $agency = Agency::factory()->create();
    $institution = Institution::factory()->create();
    $requester = User::factory()->create();

    Membership::factory()->create([
        'user_id' => $requester->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    $request = AppRequest::factory()->submitted()->create([
        'requester_id' => $requester->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($requester)
        ->get("/verification/{$request->id}")
        ->assertForbidden();
});

// ── Approve ───────────────────────────────────────────────────────────────────

test('verifier can approve a submitted request', function () {
    $verifier = verifierUser();
    $request = submittedRequest();

    $this->actingAs($verifier)
        ->post("/verification/{$request->id}/approve", ['notes' => 'Looks good.'])
        ->assertRedirect("/verification/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::Verified);

    $verification = $request->fresh()->verification;
    expect($verification)->not->toBeNull()
        ->and($verification->decision)->toBe(VerificationDecision::Approved)
        ->and($verification->notes)->toBe('Looks good.');
});

test('verifier can approve without notes', function () {
    $verifier = verifierUser();
    $request = submittedRequest();

    $this->actingAs($verifier)
        ->post("/verification/{$request->id}/approve", [])
        ->assertRedirect();

    expect($request->fresh()->status)->toBe(RequestStatus::Verified);
});

test('cannot approve already verified request', function () {
    $verifier = verifierUser();
    $request = submittedRequest();
    $request->update(['status' => RequestStatus::Verified]);

    $this->actingAs($verifier)
        ->post("/verification/{$request->id}/approve")
        ->assertForbidden();
});

// ── Reject ────────────────────────────────────────────────────────────────────

test('verifier can reject a submitted request with notes', function () {
    $verifier = verifierUser();
    $request = submittedRequest();

    $this->actingAs($verifier)
        ->post("/verification/{$request->id}/reject", ['notes' => 'Insufficient documentation.'])
        ->assertRedirect("/verification/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::VerificationRejected);

    $verification = $request->fresh()->verification;
    expect($verification->decision)->toBe(VerificationDecision::Rejected)
        ->and($verification->notes)->toBe('Insufficient documentation.');
});

test('reject requires notes', function () {
    $verifier = verifierUser();
    $request = submittedRequest();

    $this->actingAs($verifier)
        ->post("/verification/{$request->id}/reject", ['notes' => ''])
        ->assertSessionHasErrors('notes');
});

// ── Route ─────────────────────────────────────────────────────────────────────

test('verifier can route a verified request', function () {
    $verifier = verifierUser();
    $request = submittedRequest();
    $request->update(['status' => RequestStatus::Verified]);

    $this->actingAs($verifier)
        ->post("/verification/{$request->id}/route")
        ->assertRedirect("/verification/{$request->id}");

    expect($request->fresh()->status)->toBe(RequestStatus::Routed);
    expect($request->fresh()->requestRoute)->not->toBeNull();
});

test('cannot route a submitted request', function () {
    $verifier = verifierUser();
    $request = submittedRequest();

    $this->actingAs($verifier)
        ->post("/verification/{$request->id}/route")
        ->assertForbidden();
});

test('non-verifier cannot route', function () {
    $user = User::factory()->create();
    $request = submittedRequest();
    $request->update(['status' => RequestStatus::Verified]);

    $this->actingAs($user)
        ->post("/verification/{$request->id}/route")
        ->assertForbidden();
});
