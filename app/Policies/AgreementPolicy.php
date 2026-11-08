<?php

namespace App\Policies;

use App\Enums\AgreementStatus;
use App\Models\Agreement;
use App\Models\User;

class AgreementPolicy
{
    public function view(User $user, Agreement $agreement): bool
    {
        return $agreement->transaction->isParty($user);
    }

    public function approve(User $user, Agreement $agreement): bool
    {
        return $agreement->transaction->isParty($user)
            && $agreement->status === AgreementStatus::AwaitingApprovals
            && ! $agreement->approvals()->where('user_id', $user->id)->exists();
    }

    public function reject(User $user, Agreement $agreement): bool
    {
        return $agreement->transaction->isParty($user)
            && $agreement->status === AgreementStatus::AwaitingApprovals;
    }
}
