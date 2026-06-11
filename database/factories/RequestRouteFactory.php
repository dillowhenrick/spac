<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Request;
use App\Models\RequestRoute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestRoute>
 */
class RequestRouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'routed_by' => User::factory(),
            'institution_id' => Institution::factory(),
            'routed_at' => now(),
        ];
    }
}
