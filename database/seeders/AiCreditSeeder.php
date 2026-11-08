<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AiCreditService;
use Illuminate\Database\Seeder;

class AiCreditSeeder extends Seeder
{
    public function run(): void
    {
        $credits = app(AiCreditService::class);

        User::query()
            ->whereIn('email', [
                'buyer@dealflow.test',
                'seller@dealflow.test',
                'seller2@dealflow.test',
            ])
            ->get()
            ->each(function (User $user) use ($credits): void {
                $credits->credit(
                    $user,
                    1000,
                    'demo-ai-credit-'.$user->id,
                    'Demo AI credit',
                    null,
                    ['source' => 'seeder'],
                );
            });
    }
}
