<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $transaction_id
 * @property string $type
 * @property int $amount_minor
 * @property int $balance_after_minor
 * @property string $description
 * @property string $idempotency_key
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'user_id',
    'transaction_id',
    'type',
    'amount_minor',
    'balance_after_minor',
    'description',
    'idempotency_key',
    'metadata',
])]
class AiCreditLedgerEntry extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'balance_after_minor' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
