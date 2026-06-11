<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_type' => 'agency',
            'organization_id' => Agency::factory(),
            'role' => Role::Requester,
            'is_primary' => false,
        ];
    }

    public function requester(): static
    {
        return $this->state(fn () => ['role' => Role::Requester]);
    }

    public function amlakasVerifier(): static
    {
        return $this->state(fn () => ['role' => Role::AmlakasVerifier]);
    }

    public function institutionManager(): static
    {
        return $this->state(fn () => [
            'role' => Role::InstitutionManager,
            'organization_type' => 'institution',
            'organization_id' => Institution::factory(),
        ]);
    }

    public function institutionStaff(): static
    {
        return $this->state(fn () => [
            'role' => Role::InstitutionStaff,
            'organization_type' => 'institution',
            'organization_id' => Institution::factory(),
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
