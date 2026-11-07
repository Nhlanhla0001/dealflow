<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\PaymentOrder;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDealsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_reproducible_demo_workspace(): void
    {
        $this->seed();

        $this->assertDatabaseCount('transactions', 4);
        $this->assertDatabaseCount('agreements', 4);
        $this->assertDatabaseCount('payment_orders', 1);
        $this->assertDatabaseCount('notifications', 6);

        $buyer = User::query()->where('email', 'buyer@dealflow.test')->firstOrFail();
        $seller = User::query()->where('email', 'seller@dealflow.test')->firstOrFail();

        $paid = Transaction::query()->where('title', 'Business website build')->firstOrFail();
        $approved = Transaction::query()->where('title', 'E-commerce checkout redesign')->firstOrFail();
        $landing = Transaction::query()->where('title', 'AI negotiation demo — landing page')->firstOrFail();
        $productLaunch = Transaction::query()->where('title', 'AI negotiation demo 2 — product launch')->firstOrFail();

        $this->assertSame(TransactionState::Paid, $paid->state);
        $this->assertSame(TransactionState::Approved, $approved->state);
        $this->assertSame(TransactionState::AwaitingApprovals, $landing->state);
        $this->assertSame(TransactionState::AwaitingApprovals, $productLaunch->state);

        $payment = PaymentOrder::query()->where('capture_id', '84437967V6387225V')->firstOrFail();

        $this->assertSame(PaymentStatus::Captured, $payment->status);
        $this->assertSame($paid->id, $payment->transaction_id);

        $this->assertSame(4, $productLaunch->negotiationSession->messages()->count());
        $this->assertSame(3, $productLaunch->negotiationSession->agentRuns()->count());
        $this->assertSame(50000, $productLaunch->latestAgreement->price_minor);
        $this->assertSame('awaiting_approvals', $productLaunch->latestAgreement->status->value);

        $this->assertDatabaseHas('agreement_approvals', [
            'agreement_id' => $approved->latestAgreement->id,
            'user_id' => $buyer->id,
            'role' => 'buyer',
        ]);
        $this->assertDatabaseHas('agreement_approvals', [
            'agreement_id' => $approved->latestAgreement->id,
            'user_id' => $seller->id,
            'role' => 'seller',
        ]);

        $this->assertSame(0, $landing->latestAgreement->approvals()->count());
        $this->assertSame(0, $productLaunch->latestAgreement->approvals()->count());
    }
}
