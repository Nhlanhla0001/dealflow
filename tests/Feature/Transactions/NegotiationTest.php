<?php

namespace Tests\Feature\Transactions;

use App\Ai\Agents\BuyerNegotiationAgent;
use App\Ai\Agents\SellerNegotiationAgent;
use App\Enums\AgreementStatus;
use App\Enums\NegotiationStatus;
use App\Enums\OfferStatus;
use App\Enums\TransactionState;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\CounterpartyApprovedTerms;
use App\Notifications\TermsReadyForApproval;
use App\Notifications\TermsRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class NegotiationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['dealflow.ai_negotiation' => false]);
    }

    public function test_seller_can_submit_an_offer(): void
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Draft,
        ]);

        $response = $this->actingAs($transaction->seller)->post(
            route('transactions.offer.store', $transaction),
            [
                'price' => '650',
                'floor' => '450',
                'delivery_days' => 10,
                'terms' => 'Two revision rounds and payment on delivery.',
            ],
        );

        $response->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();

        $this->assertSame(65000, $transaction->seller_offer_minor);
        $this->assertSame(45000, $transaction->seller_floor_minor);
        $this->assertSame(10, $transaction->seller_delivery_days);
        $this->assertDatabaseHas('offers', [
            'transaction_id' => $transaction->id,
            'price_minor' => 65000,
            'status' => OfferStatus::Active->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'transaction_id' => $transaction->id,
            'action' => 'offer.submitted',
        ]);
    }

    public function test_ai_negotiation_requires_both_agents_to_be_active(): void
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Draft,
            'negotiation_mode' => 'ai',
        ]);

        $transaction->offer()->create([
            'seller_id' => $transaction->seller_id,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'terms' => 'Listed terms.',
            'status' => OfferStatus::Active,
        ]);

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.negotiate', $transaction))
            ->assertRedirect();

        $this->assertDatabaseCount('negotiation_sessions', 0);
    }

    public function test_buyer_cannot_submit_a_seller_offer(): void
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Draft,
        ]);

        $this->actingAs($transaction->buyer)->post(
            route('transactions.offer.store', $transaction),
            [
                'price' => '650',
                'floor' => '450',
                'delivery_days' => 10,
            ],
        )->assertForbidden();
    }

    public function test_the_floor_cannot_exceed_the_offer_price(): void
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Draft,
        ]);

        $this->actingAs($transaction->seller)->post(
            route('transactions.offer.store', $transaction),
            [
                'price' => '500',
                'floor' => '600',
                'delivery_days' => 10,
            ],
        )->assertSessionHasErrors('floor_minor');

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_buyer_can_run_a_negotiation_inside_the_limits(): void
    {
        Notification::fake();

        $transaction = $this->readyTransaction();
        $buyer = $transaction->buyer;

        $this->actingAs($buyer)->post(route('transactions.negotiate', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();

        $this->assertSame(TransactionState::AwaitingApprovals, $transaction->state);

        $session = $transaction->negotiationSession()->sole();
        $this->assertSame(NegotiationStatus::Agreed, $session->status);
        $this->assertSame(2, $session->messages()->count());

        $agreement = $transaction->latestAgreement()->sole();
        $this->assertSame(50000, $agreement->price_minor);
        $this->assertSame(7, $agreement->delivery_days);
        $this->assertSame(AgreementStatus::AwaitingApprovals, $agreement->status);

        $buyerMessage = $session->messages()->where('agent', 'buyer')->sole();
        $this->assertSame(50000, $buyerMessage->price_minor);
        $this->assertStringNotContainsString('45000', json_encode($buyerMessage->payload));

        Notification::assertSentTo($buyer, TermsReadyForApproval::class);
        Notification::assertSentTo($transaction->seller, TermsReadyForApproval::class);
    }

    public function test_negotiation_deadlocks_when_the_floor_exceeds_the_budget(): void
    {
        $transaction = $this->readyTransaction(budgetMinor: 40000);

        $this->actingAs($transaction->buyer)->post(route('transactions.negotiate', $transaction));

        $transaction->refresh();

        $this->assertSame(TransactionState::Failed, $transaction->state);
        $this->assertSame(
            NegotiationStatus::Deadlocked,
            $transaction->negotiationSession()->sole()->status,
        );
        $this->assertNull($transaction->latestAgreement);
    }

    public function test_seller_cannot_start_the_negotiation(): void
    {
        $transaction = $this->readyTransaction();

        $this->actingAs($transaction->seller)->post(route('transactions.negotiate', $transaction))
            ->assertForbidden();
    }

    public function test_ai_agents_drive_the_negotiated_terms(): void
    {
        Notification::fake();
        config(['dealflow.ai_negotiation' => true]);

        $transaction = $this->readyTransaction();

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('propose')->once()->andReturn([
                'action' => 'counteroffer',
                'price_minor' => 47000,
                'delivery_days' => 8,
                'reason' => 'A fair midpoint that fits the budget.',
            ]);
        });

        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('respond')->once()->andReturn([
                'action' => 'accept',
                'price_minor' => 47000,
                'delivery_days' => 8,
                'reason' => 'Accepted because the proposal clears the seller minimum.',
            ]);
        });

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.negotiate', $transaction));

        $agreement = $transaction->latestAgreement()->sole();
        $this->assertSame(47000, $agreement->price_minor);
        $this->assertSame(8, $agreement->delivery_days);

        $session = $transaction->negotiationSession()->sole();
        $buyerMessage = $session->messages()->where('agent', 'buyer')->sole();
        $this->assertSame(47000, $buyerMessage->price_minor);
        $this->assertSame(8, $buyerMessage->delivery_days);

        $this->assertDatabaseHas('agent_runs', [
            'negotiation_session_id' => $session->id,
            'agent' => 'buyer',
            'status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('agent_runs', [
            'negotiation_session_id' => $session->id,
            'agent' => 'seller',
            'status' => 'succeeded',
        ]);
    }

    public function test_an_ai_seller_counter_above_budget_is_capped_and_closed(): void
    {
        Notification::fake();
        config(['dealflow.ai_negotiation' => true]);

        $transaction = $this->readyTransaction();

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('propose')->twice()->andReturn(
                [
                    'action' => 'counteroffer',
                    'price_minor' => 42000,
                    'delivery_days' => 7,
                    'reason' => 'A competitive opening inside the budget.',
                ],
                [
                    'action' => 'counteroffer',
                    'price_minor' => 50000,
                    'delivery_days' => 7,
                    'reason' => 'The final proposal at the budget cap.',
                ],
            );
        });

        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('respond')->once()->andReturn([
                'action' => 'counteroffer',
                'price_minor' => 58000,
                'delivery_days' => 7,
                'reason' => 'A counter above the buyer proposal.',
            ]);
        });

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.negotiate', $transaction));

        $agreement = $transaction->latestAgreement()->sole();
        $this->assertSame(50000, $agreement->price_minor);
        $this->assertSame(7, $agreement->delivery_days);

        $session = $transaction->negotiationSession()->sole();
        $this->assertSame(4, $session->messages()->count());

        $finalBuyerMessage = $session->messages()->where('agent', 'buyer')->get()->last();
        $finalSellerMessage = $session->messages()->where('agent', 'seller')->get()->last();

        $this->assertSame(3, $finalBuyerMessage->round);
        $this->assertSame(50000, $finalBuyerMessage->price_minor);
        $this->assertSame(4, $finalSellerMessage->round);
        $this->assertSame('accept', $finalSellerMessage->action->value);
        $this->assertSame(50000, $finalSellerMessage->price_minor);
    }

    public function test_an_invalid_ai_proposal_falls_back_to_the_approved_limit(): void
    {
        Notification::fake();
        config(['dealflow.ai_negotiation' => true]);

        $transaction = $this->readyTransaction();

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('propose')->once()->andReturn([
                'action' => 'counteroffer',
                'price_minor' => 90000,
                'delivery_days' => 7,
                'reason' => 'This exceeds the budget.',
            ]);
        });

        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('respond')->once()->andReturn([
                'action' => 'accept',
                'price_minor' => 50000,
                'delivery_days' => 7,
                'reason' => 'Accepted.',
            ]);
        });

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.negotiate', $transaction));

        $agreement = $transaction->latestAgreement()->sole();
        $this->assertSame(50000, $agreement->price_minor);

        $session = $transaction->negotiationSession()->sole();
        $buyerMessage = $session->messages()->where('agent', 'buyer')->sole();
        $this->assertSame(50000, $buyerMessage->price_minor);

        $this->assertDatabaseHas('agent_runs', [
            'negotiation_session_id' => $session->id,
            'agent' => 'buyer',
            'status' => 'fallback',
        ]);
    }

    public function test_the_buyer_agent_never_receives_the_seller_floor(): void
    {
        Notification::fake();
        config(['dealflow.ai_negotiation' => true]);

        $transaction = $this->readyTransaction();
        $captured = null;

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock) use (&$captured): void {
            $mock->shouldReceive('propose')->once()->andReturnUsing(function (array $context) use (&$captured): array {
                $captured = $context;

                return [
                    'action' => 'counteroffer',
                    'price_minor' => 50000,
                    'delivery_days' => 7,
                    'reason' => 'Countered inside the budget.',
                ];
            });
        });

        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('respond')->once()->andReturn([
                'action' => 'accept',
                'price_minor' => 50000,
                'delivery_days' => 7,
                'reason' => 'Accepted.',
            ]);
        });

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.negotiate', $transaction));

        $this->assertIsArray($captured);
        $this->assertArrayNotHasKey('seller_floor_minor', $captured);

        $session = $transaction->negotiationSession()->sole();
        $sellerRun = $session->agentRuns()->where('agent', 'seller')->sole();
        $this->assertArrayNotHasKey('seller_floor_minor', $sellerRun->input);
    }

    public function test_the_seller_floor_is_redacted_from_the_ai_reason(): void
    {
        Notification::fake();
        config(['dealflow.ai_negotiation' => true]);

        $transaction = $this->readyTransaction();

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('propose')->once()->andReturn([
                'action' => 'counteroffer',
                'price_minor' => 50000,
                'delivery_days' => 7,
                'reason' => 'Countered inside the budget.',
            ]);
        });

        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('respond')->once()->andReturn([
                'action' => 'accept',
                'price_minor' => 50000,
                'delivery_days' => 7,
                'reason' => 'The 450.00 floor is acceptable for this deal.',
            ]);
        });

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.negotiate', $transaction));

        $sellerMessage = $transaction->negotiationSession()->sole()
            ->messages()->where('agent', 'seller')->sole();

        $this->assertStringNotContainsString('450', $sellerMessage->reason);
        $this->assertStringContainsString('seller minimum', $sellerMessage->reason);
    }

    public function test_both_parties_must_approve_the_same_agreement(): void
    {
        Notification::fake();

        $transaction = $this->readyTransaction();

        $this->actingAs($transaction->buyer)->post(route('transactions.negotiate', $transaction));

        $agreement = $transaction->latestAgreement()->sole();

        $this->actingAs($transaction->buyer)
            ->post(route('agreements.approve', $agreement))
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertDatabaseHas('agreement_approvals', [
            'agreement_id' => $agreement->id,
            'user_id' => $transaction->buyer_id,
            'role' => 'buyer',
        ]);
        $this->assertSame(AgreementStatus::AwaitingApprovals, $agreement->fresh()->status);
        $this->assertSame(TransactionState::AwaitingApprovals, $transaction->fresh()->state);

        Notification::assertSentTo($transaction->seller, CounterpartyApprovedTerms::class);

        $this->actingAs($transaction->seller)
            ->post(route('agreements.approve', $agreement))
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame(AgreementStatus::Approved, $agreement->fresh()->status);
        $this->assertSame(TransactionState::Approved, $transaction->fresh()->state);
    }

    public function test_an_outsider_cannot_approve_the_agreement(): void
    {
        $transaction = $this->readyTransaction();

        $this->actingAs($transaction->buyer)->post(route('transactions.negotiate', $transaction));

        $agreement = $transaction->latestAgreement()->sole();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->post(route('agreements.approve', $agreement))
            ->assertForbidden();
    }

    public function test_either_party_can_reject_the_terms(): void
    {
        Notification::fake();

        $transaction = $this->readyTransaction();

        $this->actingAs($transaction->buyer)->post(route('transactions.negotiate', $transaction));

        $agreement = $transaction->latestAgreement()->sole();

        $this->actingAs($transaction->seller)
            ->post(route('agreements.reject', $agreement))
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame(AgreementStatus::Cancelled, $agreement->fresh()->status);
        $this->assertSame(TransactionState::Cancelled, $transaction->fresh()->state);
        Notification::assertSentTo($transaction->buyer, TermsRejected::class);
    }

    public function test_opening_the_notifications_page_marks_them_as_read(): void
    {
        $transaction = $this->readyTransaction();

        $this->actingAs($transaction->buyer)->post(route('transactions.negotiate', $transaction));

        $this->assertSame(1, $transaction->buyer->unreadNotifications()->count());

        $this->actingAs($transaction->buyer)
            ->get(route('notifications.index'))
            ->assertOk();

        $this->assertSame(0, $transaction->buyer->unreadNotifications()->count());
    }

    public function test_the_seller_floor_is_hidden_from_the_buyer(): void
    {
        $transaction = $this->readyTransaction();

        $this->actingAs($transaction->buyer)
            ->get(route('transactions.show', $transaction))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('transaction.seller_floor_minor'));

        $this->actingAs($transaction->seller)
            ->get(route('transactions.show', $transaction))
            ->assertInertia(fn (Assert $page) => $page
                ->where('transaction.seller_floor_minor', 45000));
    }

    private function readyTransaction(
        int $budgetMinor = 50000,
        int $offerMinor = 65000,
        int $floorMinor = 45000,
    ): Transaction {
        $transaction = Transaction::factory()->create([
            'budget_minor' => $budgetMinor,
            'delivery_days' => 7,
            'state' => TransactionState::Draft,
        ]);

        $transaction->offer()->create([
            'seller_id' => $transaction->seller_id,
            'price_minor' => $offerMinor,
            'currency' => 'USD',
            'delivery_days' => 10,
            'status' => OfferStatus::Active,
        ]);

        $transaction->update([
            'seller_offer_minor' => $offerMinor,
            'seller_floor_minor' => $floorMinor,
            'seller_delivery_days' => 10,
        ]);

        return $transaction->fresh();
    }
}
