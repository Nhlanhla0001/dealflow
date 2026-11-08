<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $buyer_id
 * @property int $seller_id
 * @property int $amount_minor
 * @property string $currency
 * @property string $status
 * @property Carbon|null $paid_at
 * @property Carbon|null $completed_at
 */
#[Fillable([
    'transaction_id',
    'buyer_id',
    'seller_id',
    'amount_minor',
    'currency',
    'status',
    'revisions_used',
    'delivery_message',
    'delivery_url',
    'paid_at',
    'started_at',
    'delivered_at',
    'completed_at',
])]
class Order extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'revisions_used' => 'integer',
            'paid_at' => 'datetime',
            'started_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest();
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class)->latestOfMany();
    }
}
