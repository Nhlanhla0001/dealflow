<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(12),
        ];
    }

    public function from(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }
}
