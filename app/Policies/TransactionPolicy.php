<?php

namespace App\Policies;

use App\Enums\TransactionState;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $transaction->isParty($user);
    }

    public function create(User $user): bool
    {
        return $user->isBuyer();
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $transaction->buyer_id === $user->id && ! $transaction->state->isTerminal();
    }

    public function submitOffer(User $user, Transaction $transaction): bool
    {
        return $transaction->seller_id === $user->id
            && $transaction->state === TransactionState::Draft;
    }

    public function startNegotiation(User $user, Transaction $transaction): bool
    {
        return $transaction->buyer_id === $user->id
            && $transaction->state === TransactionState::Draft;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $transaction->buyer_id === $user->id && $transaction->state === TransactionState::Draft;
    }
}
