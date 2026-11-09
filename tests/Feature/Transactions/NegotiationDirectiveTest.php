<?php

namespace Tests\Feature\Transactions;

use App\Enums\NegotiationStatus;
use App\Models\NegotiationDirective;
use App\Models\NegotiationSession;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NegotiationCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegotiationDirectiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_can_send_a_directive_to_their_agent(): void
    {
        $transaction = Transaction::factory()->create();
        $session = NegotiationSession::factory()->create([
            'transaction_id' => $transaction->id,
        ]);

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.directives.store', $transaction), [
                'body' => 'Do not go above $900.',
            ])
            ->assertCreated()
            ->assertJsonPath('directive.body', 'Do not go above $900.');

        $this->assertDatabaseHas('negotiation_directives', [
            'negotiation_session_id' => $session->id,
            'user_id' => $transaction->buyer_id,
            'body' => 'Do not go above $900.',
        ]);
    }

    public function test_outsiders_cannot_send_directives(): void
    {
        $transaction = Transaction::factory()->create();
        NegotiationSession::factory()->create(['transaction_id' => $transaction->id]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('transactions.directives.store', $transaction), [
                'body' => 'Sell it for $1.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('negotiation_directives', 0);
    }

    public function test_directives_require_a_negotiation_session(): void
    {
        $transaction = Transaction::factory()->create();

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.directives.store', $transaction), [
                'body' => 'Anything.',
            ])
            ->assertStatus(422);
    }

    public function test_each_party_claims_only_its_own_directives(): void
    {
        $transaction = Transaction::factory()->create();
        $session = NegotiationSession::factory()->create([
            'transaction_id' => $transaction->id,
        ]);

        NegotiationDirective::factory()->create([
            'negotiation_session_id' => $session->id,
            'user_id' => $transaction->buyer_id,
            'body' => 'Prefer a shorter delivery window.',
        ]);

        NegotiationDirective::factory()->create([
            'negotiation_session_id' => $session->id,
            'user_id' => $transaction->seller_id,
            'body' => 'Hold the price above $700.',
        ]);

        $coordinator = app(NegotiationCoordinator::class);

        $buyerText = $coordinator->claimDirectives($session, $transaction->buyer_id);

        $this->assertStringContainsString('Prefer a shorter delivery window.', $buyerText);
        $this->assertStringNotContainsString('Hold the price above $700.', $buyerText);
        $this->assertSame(1, NegotiationDirective::query()->whereNotNull('applied_at')->count());

        // Claiming the buyer side again returns nothing: directives apply once.
        $this->assertSame('', $coordinator->claimDirectives($session, $transaction->buyer_id));

        $sellerText = $coordinator->claimDirectives($session, $transaction->seller_id);

        $this->assertStringContainsString('Hold the price above $700.', $sellerText);
        $this->assertStringNotContainsString('Prefer a shorter delivery window.', $sellerText);
        $this->assertSame(2, NegotiationDirective::query()->whereNotNull('applied_at')->count());
    }

    public function test_completed_negotiations_reject_new_directives(): void
    {
        $transaction = Transaction::factory()->create();
        NegotiationSession::factory()->create([
            'transaction_id' => $transaction->id,
            'status' => NegotiationStatus::Agreed,
        ]);

        $this->actingAs($transaction->buyer)
            ->postJson(route('transactions.directives.store', $transaction), [
                'body' => 'Anything.',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('negotiation_directives', 0);
    }
}
