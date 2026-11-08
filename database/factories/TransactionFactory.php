<?php

namespace Database\Factories;

use App\Enums\TransactionState;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory()->buyer(),
            'seller_id' => User::factory()->seller(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'currency' => 'USD',
            'budget_minor' => fake()->numberBetween(10000, 200000),
            'delivery_days' => fake()->numberBetween(3, 14),
            'negotiation_mode' => 'ai',
            'state' => TransactionState::Draft,
        ];
    }
}
