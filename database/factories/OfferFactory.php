<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'seller_id' => User::factory()->seller(),
            'price_minor' => fake()->numberBetween(10000, 200000),
            'currency' => 'USD',
            'delivery_days' => fake()->numberBetween(3, 14),
            'terms' => fake()->sentence(),
            'status' => OfferStatus::Active,
        ];
    }
}
