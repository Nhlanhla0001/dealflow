<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $agent_configuration_id
 * @property int|null $negotiation_session_id
 * @property int|null $agent_run_id
 * @property string $provider
 * @property string $model
 * @property int $prompt_tokens
 * @property int $completion_tokens
 * @property int $cost_minor
 * @property string $status
 */
#[Fillable([
    'agent_configuration_id',
    'negotiation_session_id',
    'agent_run_id',
    'provider',
    'model',
    'prompt_tokens',
    'completion_tokens',
    'cost_minor',
    'status',
])]
class AiUsageRecord extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'cost_minor' => 'integer',
        ];
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(AgentConfiguration::class, 'agent_configuration_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(NegotiationSession::class, 'negotiation_session_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'agent_run_id');
    }
}
