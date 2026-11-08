<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $opened_by
 * @property string $reason
 * @property string $description
 * @property string|null $evidence_url
 * @property string|null $evidence_path
 * @property string|null $evidence_original_name
 * @property string $status
 * @property string|null $resolution
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'order_id',
    'opened_by',
    'reason',
    'description',
    'evidence_url',
    'evidence_path',
    'evidence_original_name',
    'status',
    'resolution',
    'resolved_by',
    'resolved_at',
])]
class Dispute extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @return HasMany<DisputeEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(DisputeEvent::class)->latest();
    }
}
