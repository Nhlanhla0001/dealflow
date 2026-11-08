<?php

namespace App\Models;

use App\Enums\AgentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $transaction_id
 * @property string $role
 * @property string $provider
 * @property string $model
 * @property string $objective
 * @property array<string, mixed>|null $constraints
 * @property int $max_turns
 * @property int $spending_limit_minor
 * @property string $billing_method
 * @property AgentStatus $status
 * @property Carbon|null $activated_at
 */
#[Fillable([
    'user_id',
    'transaction_id',
    'role',
    'provider',
    'model',
    'objective',
    'constraints',
    'max_turns',
    'spending_limit_minor',
    'billing_method',
    'status',
    'activated_at',
])]
class AgentConfiguration extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'constraints' => 'array',
            'max_turns' => 'integer',
            'spending_limit_minor' => 'integer',
            'status' => AgentStatus::class,
            'activated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return HasMany<AiUsageRecord, $this>
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(AiUsageRecord::class);
    }

    public function isActive(): bool
    {
        return $this->status === AgentStatus::Active;
    }
}
