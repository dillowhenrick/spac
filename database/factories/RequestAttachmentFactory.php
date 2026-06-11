<?php

namespace Database\Factories;

use App\Models\Request;
use App\Models\RequestAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestAttachment>
 */
class RequestAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_id' => Request::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => fake()->word().'.pdf',
            'path' => 'attachments/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10000, 5000000),
        ];
    }
}
