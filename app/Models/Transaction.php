<?php

namespace App\Models;

use App\Enums\TransactionState;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int $buyer_id
 * @property int $seller_id
 * @property string $title
 * @property string $description
 * @property string $currency
 * @property int $budget_minor
 * @property int|null $seller_floor_minor
 * @property int|null $seller_offer_minor
 * @property int $delivery_days
 * @property int|null $seller_delivery_days
 * @property TransactionState $state
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'buyer_id',
    'seller_id',
    'service_id',
    'title',
    'description',
    'currency',
    'budget_minor',
    'seller_floor_minor',
    'seller_offer_minor',
    'delivery_days',
    'seller_delivery_days',
    'negotiation_mode',
    'state',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => TransactionState::class,
            'budget_minor' => 'integer',
            'seller_floor_minor' => 'integer',
            'seller_offer_minor' => 'integer',
            'delivery_days' => 'integer',
            'seller_delivery_days' => 'integer',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function offer(): HasOne
    {
        return $this->hasOne(Offer::class);
    }

    public function negotiationSession(): HasOne
    {
        return $this->hasOne(NegotiationSession::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function latestAgreement(): HasOne
    {
        return $this->hasOne(Agreement::class)->latestOfMany('version');
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function agentConfigurations(): HasMany
    {
        return $this->hasMany(AgentConfiguration::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
        });
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Builder<Transaction>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('state', [
            TransactionState::Paid->value,
            TransactionState::Cancelled->value,
            TransactionState::Failed->value,
        ]);
    }

    public function hidePrivateFieldsFrom(User $user): self
    {
        if ($this->buyer_id === $user->id) {
            $this->makeHidden('seller_floor_minor');
        }

        return $this;
    }

    public function isParty(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->seller_id === $user->id;
    }

    public function counterparty(User $user): ?User
    {
        return match ($user->id) {
            $this->buyer_id => $this->seller,
            $this->seller_id => $this->buyer,
            default => null,
        };
    }

    public function transitionTo(TransactionState $next): void
    {
        if (! $this->state->canTransitionTo($next)) {
            throw new InvalidArgumentException(
                "Cannot transition transaction from {$this->state->value} to {$next->value}."
            );
        }

        $this->state = $next;
        $this->save();
    }
}
