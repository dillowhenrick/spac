<?php

use App\Enums\Role;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\User;

test('user has memberships relationship', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    expect($user->memberships)->toHaveCount(1);
});

test('agencyMemberships scopes to agency type only', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();
    $institution = Institution::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'institution',
        'organization_id' => $institution->id,
        'role' => Role::InstitutionStaff,
    ]);

    expect($user->agencyMemberships)->toHaveCount(1)
        ->and($user->institutionMemberships)->toHaveCount(1);
});

test('hasRole returns true when user has matching role', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    expect($user->hasRole(Role::Requester))->toBeTrue()
        ->and($user->hasRole(Role::AmlakasVerifier))->toBeFalse();
});

test('hasRole returns false when user has no memberships', function () {
    $user = User::factory()->create();

    expect($user->hasRole(Role::Requester))->toBeFalse();
});

test('membership morphTo resolves agency organization', function () {
    $agency = Agency::factory()->create();

    $membership = Membership::factory()->create([
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
    ]);

    expect($membership->organization)->toBeInstanceOf(Agency::class)
        ->and($membership->organization->id)->toBe($agency->id);
});

test('membership morphTo resolves institution organization', function () {
    $institution = Institution::factory()->create();

    $membership = Membership::factory()->create([
        'organization_type' => 'institution',
        'organization_id' => $institution->id,
        'role' => Role::InstitutionManager,
    ]);

    expect($membership->organization)->toBeInstanceOf(Institution::class)
        ->and($membership->organization->id)->toBe($institution->id);
});

test('role is cast to Role enum on membership', function () {
    $membership = Membership::factory()->requester()->create();

    expect($membership->role)->toBe(Role::Requester);
});

test('inertia shared data includes memberships for authenticated user', function () {
    $user = User::factory()->create();
    $agency = Agency::factory()->create();

    Membership::factory()->create([
        'user_id' => $user->id,
        'organization_type' => 'agency',
        'organization_id' => $agency->id,
        'role' => Role::Requester,
        'is_primary' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('auth.memberships', 1)
            ->where('auth.memberships.0.role', 'requester')
            ->where('auth.memberships.0.is_primary', true)
            ->where('auth.memberships.0.organization_type', 'agency')
            ->where('auth.memberships.0.organization_name', $agency->name)
        );
});

test('inertia shared data shows empty memberships for user with none', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('auth.memberships', 0)
        );
});
