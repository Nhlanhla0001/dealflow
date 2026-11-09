<?php

namespace Database\Factories;

use App\Enums\NegotiationStatus;
use App\Models\NegotiationSession;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NegotiationSession>
 */
class NegotiationSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => Transaction::factory(),
            'status' => NegotiationStatus::Active,
            'round' => 1,
            'max_rounds' => 3,
            'started_at' => now(),
        ];
    }
}
