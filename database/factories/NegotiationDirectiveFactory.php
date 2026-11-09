<?php

namespace Database\Factories;

use App\Models\NegotiationDirective;
use App\Models\NegotiationSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NegotiationDirective>
 */
class NegotiationDirectiveFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'negotiation_session_id' => NegotiationSession::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(10),
            'applied_at' => null,
        ];
    }
}
