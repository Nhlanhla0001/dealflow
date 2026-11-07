<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id
            || $order->seller_id === $user->id
            || $user->isAdmin();
    }

    public function update(User $user, Order $order): bool
    {
        return $order->buyer_id === $user->id
            || $order->seller_id === $user->id
            || $user->isAdmin();
    }
}
