<?php

namespace App\Models;

use App\Enums\NegotiationAction;
use Database\Factories\NegotiationMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $negotiation_session_id
 * @property string $agent
 * @property int $round
 * @property NegotiationAction $action
 * @property int|null $price_minor
 * @property string|null $currency
 * @property int|null $delivery_days
 * @property string|null $reason
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'negotiation_session_id',
    'agent',
    'round',
    'action',
    'price_minor',
    'currency',
    'delivery_days',
    'reason',
    'payload',
])]
class NegotiationMessage extends Model
{
    /** @use HasFactory<NegotiationMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'action' => NegotiationAction::class,
            'price_minor' => 'integer',
            'delivery_days' => 'integer',
            'payload' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(NegotiationSession::class, 'negotiation_session_id');
    }
}
