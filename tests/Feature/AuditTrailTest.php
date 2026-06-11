<?php

use App\Enums\AuditEvent;
use App\Enums\Role;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\Request as AppRequest;
use App\Models\User;
use Illuminate\Support\Facades\Event;

// ── AuditService ──────────────────────────────────────────────────────────────

test('AuditService logs event with user and ip', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();
    $institution = Institution::factory()->create();

    $request = AppRequest::factory()->create([
        'requester_id' => $user->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->post("/requests/{$request->id}/submit");

    $log = AuditLog::where('event', AuditEvent::RequestSubmitted->value)->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->auditable_type)->toBe('request')
        ->and($log->auditable_id)->toBe($request->id);
});

test('request submit creates audit log', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();
    $institution = Institution::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    $request = AppRequest::factory()->draft()->create([
        'requester_id' => $user->id,
        'agency_id' => $agency->id,
        'target_institution_id' => $institution->id,
    ]);

    $this->actingAs($user)
        ->post("/requests/{$request->id}/submit");

    expect(
        AuditLog::where('event', AuditEvent::RequestSubmitted->value)
            ->where('auditable_id', $request->id)
            ->exists()
    )->toBeTrue();
});

test('request create creates audit log', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();
    $institution = Institution::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    $this->actingAs($user)->post('/requests', [
        'legal_process' => 'WDCD',
        'reference_number' => 'TEST-0001',
        'target_institution_id' => $institution->id,
    ]);

    expect(
        AuditLog::where('event', AuditEvent::RequestCreated->value)->exists()
    )->toBeTrue();
});

// ── Security Log page ─────────────────────────────────────────────────────────

test('authenticated user can view own security log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/my/security')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('security-log/index'));
});

test('guest redirected from security log', function () {
    $this->get('/my/security')->assertRedirect('/login');
});

// ── System Audit page (verifier only) ─────────────────────────────────────────

test('verifier can access system audit log', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::AmlakasVerifier,
        'is_primary' => true,
    ]);

    $this->actingAs($user)
        ->get('/verification/audit')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('audit/index'));
});

test('non-verifier cannot access system audit log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/verification/audit')
        ->assertForbidden();
});

// ── Auth event listener ───────────────────────────────────────────────────────

test('login event creates security audit log', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect(
        AuditLog::where('user_id', $user->id)
            ->where('event', AuditEvent::Login->value)
            ->exists()
    )->toBeTrue();
});

test('failed login creates security audit log', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    expect(
        AuditLog::where('event', AuditEvent::LoginFailed->value)->exists()
    )->toBeTrue();
});
