<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use Database\Factories\AgreementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $version
 * @property int $price_minor
 * @property string $currency
 * @property int $delivery_days
 * @property array<string, mixed> $terms
 * @property AgreementStatus $status
 * @property string $hash
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'transaction_id',
    'version',
    'price_minor',
    'currency',
    'delivery_days',
    'terms',
    'status',
    'hash',
])]
class Agreement extends Model
{
    /** @use HasFactory<AgreementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'price_minor' => 'integer',
            'delivery_days' => 'integer',
            'terms' => 'array',
            'status' => AgreementStatus::class,
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AgreementApproval::class);
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function isApprovedByBothParties(): bool
    {
        return $this->approvals()->count() >= 2;
    }
}
