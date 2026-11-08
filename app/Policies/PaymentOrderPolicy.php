<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\PaymentOrder;
use App\Models\Transaction;
use App\Models\User;

class PaymentOrderPolicy
{
    public function view(User $user, PaymentOrder $paymentOrder): bool
    {
        return $paymentOrder->transaction->isParty($user);
    }

    public function create(User $user, Transaction $transaction): bool
    {
        return $transaction->buyer_id === $user->id
            && $transaction->state === TransactionState::Approved;
    }

    public function capture(User $user, PaymentOrder $paymentOrder): bool
    {
        return $paymentOrder->transaction->buyer_id === $user->id
            && $paymentOrder->status === PaymentStatus::Created
            && $paymentOrder->transaction->state === TransactionState::PaymentPending;
    }

    public function cancel(User $user, PaymentOrder $paymentOrder): bool
    {
        return $paymentOrder->transaction->buyer_id === $user->id
            && in_array($paymentOrder->status, [
                PaymentStatus::Created,
                PaymentStatus::Pending,
            ], true);
    }
}
