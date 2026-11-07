<?php

namespace App\Services;

use App\Exceptions\InsufficientAiCreditException;
use App\Models\AiCreditLedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AiCreditService
{
    public function balance(User $user): int
    {
        return (int) AiCreditLedgerEntry::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->value('balance_after_minor');
    }

    public function canSpend(User $user, int $amountMinor): bool
    {
        return $this->balance($user) >= $amountMinor;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function credit(
        User $user,
        int $amountMinor,
        string $idempotencyKey,
        string $description,
        ?Transaction $transaction = null,
        array $metadata = [],
    ): AiCreditLedgerEntry {
        return DB::transaction(function () use (
            $user,
            $amountMinor,
            $idempotencyKey,
            $description,
            $transaction,
            $metadata,
        ): AiCreditLedgerEntry {
            $existing = AiCreditLedgerEntry::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing instanceof AiCreditLedgerEntry) {
                return $existing;
            }

            User::query()->lockForUpdate()->findOrFail($user->id);

            $balance = $this->balance($user) + $amountMinor;

            return AiCreditLedgerEntry::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction?->id,
                'type' => 'credit',
                'amount_minor' => $amountMinor,
                'balance_after_minor' => $balance,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function debit(
        User $user,
        int $amountMinor,
        string $idempotencyKey,
        string $description,
        ?Transaction $transaction = null,
        array $metadata = [],
    ): AiCreditLedgerEntry {
        return DB::transaction(function () use (
            $user,
            $amountMinor,
            $idempotencyKey,
            $description,
            $transaction,
            $metadata,
        ): AiCreditLedgerEntry {
            $existing = AiCreditLedgerEntry::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing instanceof AiCreditLedgerEntry) {
                return $existing;
            }

            User::query()->lockForUpdate()->findOrFail($user->id);

            $balance = $this->balance($user);

            if ($balance < $amountMinor) {
                throw new InsufficientAiCreditException($balance, $amountMinor);
            }

            return AiCreditLedgerEntry::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction?->id,
                'type' => 'debit',
                'amount_minor' => -$amountMinor,
                'balance_after_minor' => $balance - $amountMinor,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
            ]);
        });
    }
}
