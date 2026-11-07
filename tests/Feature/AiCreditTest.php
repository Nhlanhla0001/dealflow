<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientAiCreditException;
use App\Models\AiCreditLedgerEntry;
use App\Models\User;
use App\Services\AiCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_and_debit_entries_are_idempotent_and_keep_a_balance(): void
    {
        $user = User::factory()->buyer()->create();
        $credits = app(AiCreditService::class);

        $first = $credits->credit($user, 1000, 'credit-1', 'Demo credit');
        $second = $credits->credit($user, 1000, 'credit-1', 'Demo credit');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1000, $credits->balance($user));

        $debit = $credits->debit($user, 250, 'debit-1', 'Agent usage');
        $this->assertSame(750, $credits->balance($user));
        $this->assertSame(-250, $debit->amount_minor);
        $this->assertSame(1, AiCreditLedgerEntry::query()->where('idempotency_key', 'debit-1')->count());
    }

    public function test_a_debit_cannot_exceed_the_available_balance(): void
    {
        $user = User::factory()->buyer()->create();
        $credits = app(AiCreditService::class);

        $this->expectException(InsufficientAiCreditException::class);

        $credits->debit($user, 500, 'debit-too-large', 'Too much');
    }

    public function test_the_ai_usage_page_shows_the_balance_and_can_add_sandbox_credit(): void
    {
        $user = User::factory()->buyer()->create();
        app(AiCreditService::class)->credit($user, 1000, 'page-credit', 'Page credit');

        $this->actingAs($user)
            ->get(route('ai.usage'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ai/usage')
                ->where('balanceMinor', 1000)
                ->has('entries', 1));

        $this->actingAs($user)
            ->post(route('ai.credits.store'))
            ->assertRedirect();

        $this->assertSame(1500, app(AiCreditService::class)->balance($user));
    }
}
