<?php

namespace App\Actions\Agreements;

use App\Enums\AgreementStatus;
use App\Enums\TransactionState;
use App\Models\Agreement;
use App\Models\User;
use App\Notifications\TermsRejected;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RejectAgreement
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
            $agreement->update(['status' => AgreementStatus::Cancelled]);

            if ($transaction->state === TransactionState::AwaitingApprovals) {
                $transaction->transitionTo(TransactionState::Cancelled);
            }

            $this->audit->record($user, $transaction, 'agreement.rejected', [
                'agreement_id' => $agreement->id,
            ]);

            $counterparty = $transaction->buyer_id === $user->id
                ? $transaction->seller
                : $transaction->buyer;

            $counterparty?->notify(new TermsRejected($agreement, $user));
        });
    }
}
