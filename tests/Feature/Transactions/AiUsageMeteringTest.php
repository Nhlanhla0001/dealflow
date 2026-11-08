<?php

namespace Tests\Feature\Transactions;

use App\Ai\Agents\BuyerNegotiationAgent;
use App\Ai\Agents\SellerNegotiationAgent;
use App\Enums\AgentStatus;
use App\Enums\NegotiationStatus;
use App\Enums\OfferStatus;
use App\Enums\TransactionState;
use App\Models\AgentConfiguration;
use App\Models\AiUsageRecord;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiCreditService;
use App\Services\NegotiationCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AiUsageMeteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_calls_create_usage_records_and_debit_the_ledger(): void
    {
        [$transaction, $buyer, $seller] = $this->aiTransaction();
        $this->credit($buyer);
        $this->credit($seller);

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('propose')->once()->andReturn([
                'action' => 'counteroffer',
                'price_minor' => 46000,
                'delivery_days' => 7,
                'reason' => 'Inside my budget.',
            ]);
        });

        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('respond')->once()->andReturn([
                'action' => 'accept',
                'price_minor' => 46000,
                'delivery_days' => 7,
                'reason' => 'Meets my floor.',
            ]);
        });

        app(NegotiationCoordinator::class)->run(
            $transaction->negotiationSession()->create([
                'status' => NegotiationStatus::Pending,
                'round' => 0,
                'max_rounds' => 5,
            ]),
        );

        $this->assertSame(2, AiUsageRecord::query()->count());
        $this->assertDatabaseCount('agent_runs', 2);
        $this->assertSame(999, app(AiCreditService::class)->balance($buyer));
        $this->assertSame(999, app(AiCreditService::class)->balance($seller));
        $this->assertDatabaseHas('ai_usage_records', [
            'provider' => 'gemini',
            'model' => 'gemini-3-flash-preview',
            'cost_minor' => 1,
        ]);
    }

    public function test_agent_execution_stops_when_the_ai_credit_is_exhausted(): void
    {
        [$transaction, $buyer, $seller] = $this->aiTransaction();
        $this->credit($buyer, 0);
        $this->credit($seller, 0);

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('propose');
        });
        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('respond');
        });

        app(NegotiationCoordinator::class)->run(
            $transaction->negotiationSession()->create([
                'status' => NegotiationStatus::Pending,
                'round' => 0,
                'max_rounds' => 5,
            ]),
        );

        $this->assertSame(0, AiUsageRecord::query()->count());
        $this->assertDatabaseHas('agent_runs', [
            'agent' => 'buyer',
            'status' => 'fallback',
            'error' => 'The buyer AI budget is exhausted.',
        ]);
    }

    public function test_the_session_stops_at_the_configured_turn_limit(): void
    {
        [$transaction, $buyer, $seller] = $this->aiTransaction(maxTurns: 1);
        $this->credit($buyer);
        $this->credit($seller);

        $this->mock(BuyerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldReceive('propose')->once()->andReturn([
                'action' => 'counteroffer',
                'price_minor' => 40000,
                'delivery_days' => 7,
                'reason' => 'Opening counter.',
            ]);
        });
        $this->mock(SellerNegotiationAgent::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('respond');
        });

        $session = $transaction->negotiationSession()->create([
            'status' => NegotiationStatus::Pending,
            'round' => 0,
            'max_rounds' => 1,
        ]);

        app(NegotiationCoordinator::class)->run($session);

        $this->assertSame(NegotiationStatus::Deadlocked, $session->fresh()->status);
        $this->assertSame(1, AiUsageRecord::query()->count());
    }

    private function credit(User $user, int $amount = 1000): void
    {
        if ($amount === 0) {
            return;
        }

        app(AiCreditService::class)->credit(
            $user,
            $amount,
            'metering-credit-'.$user->id.'-'.$amount,
            'Metering test credit',
        );
    }

    /**
     * @return array{0: Transaction, 1: User, 2: User}
     */
    private function aiTransaction(int $maxTurns = 5): array
    {
        $buyer = User::factory()->buyer()->create();
        $seller = User::factory()->seller()->create();

        $transaction = Transaction::factory()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'state' => TransactionState::Draft,
            'negotiation_mode' => 'ai',
            'budget_minor' => 50000,
            'seller_offer_minor' => 50000,
            'seller_floor_minor' => 45000,
            'delivery_days' => 7,
        ]);

        $transaction->offer()->create([
            'seller_id' => $seller->id,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'terms' => 'Test terms.',
            'status' => OfferStatus::Active,
        ]);

        foreach ([[$buyer, 'buyer', ['max_budget_minor' => 50000]], [$seller, 'seller', ['floor_minor' => 45000]]] as [$user, $role, $constraints]) {
            AgentConfiguration::query()->create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'role' => $role,
                'provider' => 'gemini',
                'model' => 'gemini-3-flash-preview',
                'objective' => 'Test objective.',
                'constraints' => $constraints,
                'max_turns' => $maxTurns,
                'spending_limit_minor' => 100,
                'billing_method' => 'prepaid_credits',
                'status' => AgentStatus::Active,
            ]);
        }

        return [$transaction, $buyer, $seller];
    }
}
