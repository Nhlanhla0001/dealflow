<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Throwable;

class BuyerNegotiationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are the buyer\'s negotiating agent. The JSON context contains the buyer\'s maximum budget, the seller\'s opening offer, and the delivery window. Propose terms at or below budget_minor. Never exceed the budget. When final_round is true, propose a final counteroffer that does not exceed budget_minor. Do not invent or mention private seller limits. Be concise and return a structured proposal.';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['offer', 'counteroffer', 'accept', 'reject'])->required(),
            'price_minor' => $schema->integer()->min(1)->required(),
            'delivery_days' => $schema->integer()->min(1)->max(365)->required(),
            'reason' => $schema->string()->max(300)->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function propose(array $context): ?array
    {
        try {
            $response = $this->prompt(json_encode($context, JSON_THROW_ON_ERROR));

            return is_array($response->structured) ? $response->structured : null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
