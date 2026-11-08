<?php

namespace App\Models;

use App\Enums\WizardStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $wizard_type
 * @property string|null $context_type
 * @property int|null $context_id
 * @property string $current_step
 * @property array<string, mixed>|null $data
 * @property array<int, string>|null $completed_steps
 * @property int $version
 * @property WizardStatus $status
 * @property Carbon|null $expires_at
 */
#[Fillable([
    'uuid',
    'user_id',
    'wizard_type',
    'context_type',
    'context_id',
    'current_step',
    'data',
    'completed_steps',
    'version',
    'status',
    'expires_at',
])]
class WizardDraft extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'completed_steps' => 'array',
            'version' => 'integer',
            'status' => WizardStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WizardDraft $draft): void {
            $draft->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === WizardStatus::Completed;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * @param  Builder<WizardDraft>  $query
     * @return Builder<WizardDraft>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', WizardStatus::Draft->value)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
