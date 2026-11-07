<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;

class AuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(?User $user, ?Transaction $transaction, string $action, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user?->id,
            'transaction_id' => $transaction?->id,
            'action' => $action,
            'metadata' => $metadata ?: null,
        ]);
    }
}
