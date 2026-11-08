<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $negotiation_session_id
 * @property string $agent
 * @property string|null $provider
 * @property string|null $model
 * @property string $status
 * @property array<string, mixed>|null $input
 * @property array<string, mixed>|null $output
 * @property string|null $error
 * @property int|null $prompt_tokens
 * @property int|null $completion_tokens
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'negotiation_session_id',
    'agent',
    'provider',
    'model',
    'status',
    'input',
    'output',
    'error',
    'prompt_tokens',
    'completion_tokens',
])]
class AgentRun extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NegotiationSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(NegotiationSession::class, 'negotiation_session_id');
    }
}
