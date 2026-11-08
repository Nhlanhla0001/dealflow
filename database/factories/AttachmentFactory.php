<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'collection' => 'default',
            'disk' => 'local',
            'path' => 'attachments/'.fake()->uuid().'.txt',
            'original_name' => 'notes.txt',
            'mime' => 'text/plain',
            'size' => 128,
        ];
    }

    public function publicImage(): static
    {
        return $this->state(fn (): array => [
            'collection' => 'service_image',
            'disk' => 'public',
            'path' => 'service-images/'.fake()->uuid().'.jpg',
            'original_name' => 'work.jpg',
            'mime' => 'image/jpeg',
            'size' => 2048,
        ]);
    }
}
