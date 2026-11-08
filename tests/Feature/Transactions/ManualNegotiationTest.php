<?php

namespace Tests\Feature\Transactions;

use App\Enums\NegotiationStatus;
use App\Enums\OfferStatus;
use App\Enums\TransactionState;
use App\Models\Transaction;
use App\Notifications\TermsReadyForApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManualNegotiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_negotiation_can_produce_an_agreement_without_ai(): void
    {
        Notification::fake();

        $transaction = $this->manualTransaction();

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.negotiate', $transaction))
            ->assertRedirect();

        $this->assertDatabaseCount('agent_runs', 0);
        $this->assertSame(TransactionState::Negotiating, $transaction->fresh()->state);

        $this->actingAs($transaction->seller)
            ->post(route('transactions.manual-offer', $transaction), [
                'price' => 480,
                'delivery_days' => 7,
                'reason' => 'I can meet you at 480.',
            ])
            ->assertRedirect();

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.manual-accept', $transaction))
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame(TransactionState::AwaitingApprovals, $transaction->state);
        $this->assertSame(NegotiationStatus::Agreed, $transaction->negotiationSession->status);
        $this->assertSame(48000, $transaction->latestAgreement->price_minor);
        Notification::assertSentTo(
            [$transaction->buyer, $transaction->seller],
            TermsReadyForApproval::class,
        );
    }

    public function test_manual_offers_respect_private_limits(): void
    {
        $transaction = $this->manualTransaction();

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.manual-offer', $transaction), [
                'price' => 600,
                'delivery_days' => 7,
                'reason' => 'Above budget.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('negotiation_messages', 0);

        $this->actingAs($transaction->seller)
            ->post(route('transactions.manual-offer', $transaction), [
                'price' => 400,
                'delivery_days' => 7,
                'reason' => 'Below floor.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('negotiation_messages', 0);
    }

    private function manualTransaction(): Transaction
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Draft,
            'negotiation_mode' => 'manual',
            'budget_minor' => 50000,
            'seller_offer_minor' => 50000,
            'seller_floor_minor' => 45000,
            'delivery_days' => 7,
        ]);

        $transaction->offer()->create([
            'seller_id' => $transaction->seller_id,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'terms' => 'Manual terms.',
            'status' => OfferStatus::Active,
        ]);

        return $transaction;
    }
}
