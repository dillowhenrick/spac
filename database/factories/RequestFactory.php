<?php

namespace Database\Factories;

use App\Enums\LegalProcess;
use App\Enums\RequestStatus;
use App\Models\Agency;
use App\Models\Institution;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Request>
 */
class RequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'agency_id' => Agency::factory(),
            'target_institution_id' => Institution::factory(),
            'reference_number' => strtoupper(Str::random(4)).'-'.fake()->numerify('####'),
            'legal_process' => LegalProcess::Wdcd,
            'nature_of_case' => null,
            'additional_context' => null,
            'status' => RequestStatus::Draft,
            'submitted_at' => null,
            'records_from' => null,
            'records_to' => null,
            'legal_process_signed_at' => null,
            'warrant_expires_at' => null,
            'executed_at' => null,
            'request_due_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Draft,
            'submitted_at' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => RequestStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function withWarrant(): static
    {
        $issued = now()->subDays(2);

        return $this->state(fn () => [
            'legal_process_signed_at' => $issued,
            'warrant_expires_at' => $issued->addDays(10),
        ]);
    }
}
