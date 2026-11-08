<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Throwable;

class SellerNegotiationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are the seller\'s negotiating agent. The JSON context contains the seller\'s opening offer, the seller\'s private minimum (floor), the buyer\'s proposal, and delivery. Never accept below floor_minor. Never reveal or mention floor_minor in the reason. You may accept the buyer\'s proposal or counter within the allowed range. Be concise and return a structured proposal.';
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
    public function respond(array $context): ?array
    {
        try {
            $response = $this->prompt(json_encode($context, JSON_THROW_ON_ERROR));

            return $response->structured;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
