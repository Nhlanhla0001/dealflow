<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $buyer = User::factory()->buyer()->create([
            'name' => 'Demo Buyer',
            'email' => 'buyer@dealflow.test',
        ]);

        $seller = User::factory()->seller()->create([
            'name' => 'Demo Seller',
            'email' => 'seller@dealflow.test',
        ]);

        User::factory()->seller()->create([
            'name' => 'Second Seller',
            'email' => 'seller2@dealflow.test',
        ]);

        Transaction::factory()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'title' => 'Business website build',
            'description' => 'A seven-page marketing site with a contact form, CMS, and launch support.',
            'budget_minor' => 50000,
            'delivery_days' => 7,
        ]);
    }
}
