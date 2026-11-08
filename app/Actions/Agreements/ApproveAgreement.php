<?php

namespace App\Actions\Agreements;

use App\Enums\AgreementStatus;
use App\Enums\TransactionState;
use App\Models\Agreement;
use App\Models\User;
use App\Notifications\CounterpartyApprovedTerms;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApproveAgreement
{
    public function __construct(private readonly AuditService $audit) {}

    public function handle(Agreement $agreement, User $user): void
    {
        DB::transaction(function () use ($agreement, $user): void {
            $agreement->loadMissing(['transaction.buyer', 'transaction.seller']);

            if ($agreement->status !== AgreementStatus::AwaitingApprovals) {
                throw new InvalidArgumentException('This agreement is not awaiting approvals.');
            }

            $transaction = $agreement->transaction;
            $role = $transaction->buyer_id === $user->id ? 'buyer' : 'seller';

            $agreement->approvals()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $role, 'approved_at' => now()],
            );

            $this->audit->record($user, $transaction, 'agreement.approved', [
                'agreement_id' => $agreement->id,
                'role' => $role,
            ]);

            if ($agreement->approvals()->count() < 2) {
                $counterparty = $transaction->buyer_id === $user->id
                    ? $transaction->seller
                    : $transaction->buyer;

                $counterparty?->notify(new CounterpartyApprovedTerms($agreement, $user));

                return;
            }

            $agreement->update(['status' => AgreementStatus::Approved]);

            if ($transaction->state === TransactionState::AwaitingApprovals) {
                $transaction->transitionTo(TransactionState::Approved);
            }

            $this->audit->record($user, $transaction, 'agreement.fully_approved', [
                'agreement_id' => $agreement->id,
            ]);
        });
    }
}
