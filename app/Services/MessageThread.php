<?php

namespace App\Services;

use App\Models\MessageRead;
use App\Models\Transaction;
use App\Models\User;

class MessageThread
{
    public function unreadCount(Transaction $transaction, User $user): int
    {
        $lastReadAt = $transaction->messageReads()
            ->where('user_id', $user->id)
            ->value('last_read_at');

        return $transaction->messages()
            ->where('user_id', '!=', $user->id)
            ->when(
                $lastReadAt !== null,
                fn ($query) => $query->where('created_at', '>', $lastReadAt),
            )
            ->count();
    }

    public function markRead(Transaction $transaction, User $user): void
    {
        MessageRead::query()->updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
            ],
            ['last_read_at' => now()],
        );
    }
}
