<?php

namespace App\Services;

use App\Exceptions\InsufficientAiCreditException;
use App\Models\AgentConfiguration;
use App\Models\AgentRun;
use App\Models\AiUsageRecord;
use Illuminate\Support\Facades\DB;

class AiUsageService
{
    public function __construct(private readonly AiCreditService $credits) {}

    public function canRun(AgentConfiguration $configuration, int $costMinor): bool
    {
        return $this->credits->canSpend($configuration->user, $costMinor);
    }

    public function record(
        AgentRun $run,
        AgentConfiguration $configuration,
        int $costMinor,
        int $promptTokens,
        int $completionTokens,
    ): AiUsageRecord {
        return DB::transaction(function () use (
            $run,
            $configuration,
            $costMinor,
            $promptTokens,
            $completionTokens,
        ): AiUsageRecord {
            $usage = AiUsageRecord::query()->firstOrCreate(
                ['agent_run_id' => $run->id],
                [
                    'agent_configuration_id' => $configuration->id,
                    'negotiation_session_id' => $run->negotiation_session_id,
                    'provider' => $configuration->provider,
                    'model' => $configuration->model,
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'cost_minor' => $costMinor,
                    'status' => 'recorded',
                ],
            );

            if ($usage->wasRecentlyCreated && $costMinor > 0) {
                try {
                    $this->credits->debit(
                        $configuration->user,
                        $costMinor,
                        'ai-usage-'.$run->id,
                        'AI usage for agent run #'.$run->id,
                        $run->session?->transaction,
                        [
                            'agent_run_id' => $run->id,
                            'provider' => $configuration->provider,
                            'model' => $configuration->model,
                        ],
                    );
                } catch (InsufficientAiCreditException) {
                    $usage->update(['status' => 'insufficient_credit']);
                }
            }

            return $usage;
        });
    }
}
