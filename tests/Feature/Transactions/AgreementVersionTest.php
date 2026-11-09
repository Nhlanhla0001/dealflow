<?php

namespace Tests\Feature\Transactions;

use App\Actions\Agreements\ApproveAgreement;
use App\Enums\AgreementStatus;
use App\Enums\TransactionState;
use App\Models\Transaction;
use App\Services\AgreementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class AgreementVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_agreement_version_supersedes_approvals_and_blocks_checkout(): void
    {
        Notification::fake();

        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Negotiating,
        ]);

        $agreements = app(AgreementService::class);
        $approve = app(ApproveAgreement::class);

        $first = $agreements->create($transaction, 50000, 7);

        $approve->handle($first, $transaction->buyer);
        $approve->handle($first, $transaction->seller);

        $this->assertSame(AgreementStatus::Approved, $first->fresh()->status);
        $this->assertSame(TransactionState::Approved, $transaction->fresh()->state);
        $this->assertSame(2, $first->fresh()->approvals()->count());

        $second = $agreements->create($transaction, 52000, 9);

        $this->assertSame(AgreementStatus::Superseded, $first->fresh()->status);
        $this->assertSame(AgreementStatus::AwaitingApprovals, $second->status);
        $this->assertSame(2, $second->version);
        $this->assertSame(TransactionState::AwaitingApprovals, $transaction->fresh()->state);

        // The old approvals still exist, but they cannot authorize payment for
        // the new version.
        $this->assertSame(2, $first->fresh()->approvals()->count());
        $this->assertSame(0, $second->approvals()->count());

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.checkout', $transaction))
            ->assertForbidden();

        $this->assertDatabaseCount('payment_orders', 0);
    }

    public function test_a_new_agreement_cannot_be_created_after_payment_starts(): void
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::PaymentPending,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(AgreementService::class)->create($transaction, 50000, 7);
    }
}
