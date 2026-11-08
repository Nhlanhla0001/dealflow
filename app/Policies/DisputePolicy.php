<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function view(User $user, Dispute $dispute): bool
    {
        $order = $dispute->order;

        return $user->isAdmin()
            || $order->buyer_id === $user->id
            || $order->seller_id === $user->id;
    }

    public function update(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin();
    }
}
