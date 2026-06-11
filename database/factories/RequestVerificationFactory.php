<?php

namespace Database\Factories;

use App\Enums\VerificationDecision;
use App\Models\Request;
use App\Models\RequestVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestVerification>
 */
class RequestVerificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory()->submitted(),
            'verified_by' => User::factory(),
            'decision' => VerificationDecision::Approved,
            'notes' => null,
            'verified_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'decision' => VerificationDecision::Approved,
            'notes' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'decision' => VerificationDecision::Rejected,
            'notes' => fake()->sentence(),
        ]);
    }
}
