<?php

namespace Database\Seeders;

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

        User::factory()->admin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@dealflow.test',
        ]);

        $this->call(DemoDealsSeeder::class);
        $this->call(MarketplaceSeeder::class);
        $this->call(AiCreditSeeder::class);
    }
}
