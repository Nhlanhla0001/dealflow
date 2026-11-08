<?php

namespace App\Services;

use App\Ai\Agents\BuyerNegotiationAgent;
use App\Ai\Agents\SellerNegotiationAgent;
use App\Enums\NegotiationStatus;
use App\Enums\TransactionState;
use App\Models\AgentConfiguration;
use App\Models\AgentRun;
use App\Models\NegotiationSession;
use App\Models\Transaction;
use App\Notifications\TermsReadyForApproval;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class NegotiationCoordinator
{
    public function __construct(
        private readonly OfferValidator $validator,
        private readonly AgreementService $agreements,
        private readonly BuyerNegotiationAgent $buyerAgent,
        private readonly SellerNegotiationAgent $sellerAgent,
        private readonly AiUsageService $usage,
        private readonly AuditService $audit,
    ) {}

    public function run(NegotiationSession $session): void
    {
        $transaction = $session->transaction()
            ->with(['buyer', 'seller', 'offer'])
            ->firstOrFail();

        $offer = $transaction->offer;

        if (! $offer) {
            throw new RuntimeException('The seller must submit an offer before negotiation.');
        }

        $buyerMax = (int) $transaction->budget_minor;
        $sellerFloor = (int) ($transaction->seller_floor_minor ?? $offer->price_minor);
        $sellerOffer = (int) $offer->price_minor;
        $deliveryWindow = min((int) $transaction->delivery_days, (int) $offer->delivery_days);

        $this->validator->assertDeliveryDays($deliveryWindow);

        $session->update([
            'status' => NegotiationStatus::Active,
            'started_at' => now(),
            'round' => 1,
        ]);

        if ($sellerFloor > $buyerMax) {
            $this->deadlock($session, $transaction, $buyerMax, $deliveryWindow);

            return;
        }

        $buyerProposal = $this->buyerProposal(
            $session,
            $transaction,
            $buyerMax,
            $sellerOffer,
            $deliveryWindow,
        );

        if ($buyerProposal === null || $buyerProposal['action'] === 'reject') {
            $this->deadlock($session, $transaction, $buyerMax, $deliveryWindow);

            return;
        }

        $buyerPrice = (int) $buyerProposal['price_minor'];
        $buyerDelivery = (int) $buyerProposal['delivery_days'];

        if ($buyerProposal['action'] === 'accept') {
            $buyerPrice = $sellerOffer;
        }

        $this->validator->assertBuyerProposal($buyerPrice, $buyerMax);
        $this->validator->assertDeliveryDays($buyerDelivery);

        $this->storeMessage(
            $session,
            agent: 'buyer',
            round: 1,
            action: $buyerProposal['action'],
            priceMinor: $buyerPrice,
            currency: $transaction->currency,
            deliveryDays: $buyerDelivery,
            reason: $buyerProposal['reason'],
        );

        if ($buyerProposal['action'] === 'accept') {
            $this->storeMessage(
                $session,
                agent: 'seller',
                round: 2,
                action: 'accept',
                priceMinor: $sellerOffer,
                currency: $transaction->currency,
                deliveryDays: $deliveryWindow,
                reason: 'Accepted the terms because they meet the seller minimum and delivery range.',
            );

            $this->complete($session, $transaction, $sellerOffer, $deliveryWindow, 2);

            return;
        }

        if ($session->max_rounds < 2) {
            $this->deadlock($session, $transaction, $buyerPrice, $deliveryWindow);

            return;
        }

        $sellerProposal = $this->sellerProposal(
            $session,
            $transaction,
            $sellerOffer,
            $sellerFloor,
            $buyerPrice,
            $buyerDelivery,
        );

        if ($sellerProposal === null || $sellerProposal['action'] === 'reject') {
            $this->deadlock($session, $transaction, $buyerMax, $deliveryWindow);

            return;
        }

        $sellerAction = $sellerProposal['action'];
        $sellerPrice = (int) $sellerProposal['price_minor'];
        $sellerDelivery = (int) $sellerProposal['delivery_days'];

        if ($sellerAction === 'accept') {
            $sellerPrice = $buyerPrice;
            $sellerDelivery = $buyerDelivery;
        }

        $this->validator->assertSellerProposal($sellerPrice, $sellerFloor);
        $this->validator->assertDeliveryDays($sellerDelivery);

        $this->storeMessage(
            $session,
            agent: 'seller',
            round: 2,
            action: $sellerAction,
            priceMinor: $sellerPrice,
            currency: $transaction->currency,
            deliveryDays: $sellerDelivery,
            reason: $sellerProposal['reason'],
        );

        if ($sellerAction === 'accept') {
            $this->complete($session, $transaction, $buyerPrice, $buyerDelivery, 2);

            return;
        }

        if ($sellerPrice <= $buyerMax) {
            $this->storeMessage(
                $session,
                agent: 'buyer',
                round: 3,
                action: 'accept',
                priceMinor: $sellerPrice,
                currency: $transaction->currency,
                deliveryDays: $sellerDelivery,
                reason: 'Accepted the seller counter because it stays inside the approved budget.',
            );

            $this->complete($session, $transaction, $sellerPrice, $sellerDelivery, 3);

            return;
        }

        if ($session->max_rounds < 3) {
            $this->deadlock($session, $transaction, $sellerPrice, $deliveryWindow);

            return;
        }

        $buyerFollowUp = $this->buyerFollowUpProposal(
            $session,
            $transaction,
            $buyerMax,
            $sellerOffer,
            $sellerPrice,
            $deliveryWindow,
        );

        if ($buyerFollowUp === null || $buyerFollowUp['action'] === 'reject') {
            $this->deadlock($session, $transaction, $buyerMax, $deliveryWindow);

            return;
        }

        $finalBuyerPrice = (int) $buyerFollowUp['price_minor'];
        $finalBuyerDelivery = min((int) $buyerFollowUp['delivery_days'], $deliveryWindow);

        if ($finalBuyerPrice > $buyerMax || $finalBuyerPrice < $sellerFloor) {
            $finalBuyerPrice = $buyerMax;
        }

        $this->validator->assertBuyerProposal($finalBuyerPrice, $buyerMax);
        $this->validator->assertDeliveryDays($finalBuyerDelivery);

        $this->storeMessage(
            $session,
            agent: 'buyer',
            round: 3,
            action: 'counteroffer',
            priceMinor: $finalBuyerPrice,
            currency: $transaction->currency,
            deliveryDays: $finalBuyerDelivery,
            reason: $buyerFollowUp['reason'],
        );

        if ($finalBuyerPrice >= $sellerFloor) {
            $this->storeMessage(
                $session,
                agent: 'seller',
                round: 4,
                action: 'accept',
                priceMinor: $finalBuyerPrice,
                currency: $transaction->currency,
                deliveryDays: $finalBuyerDelivery,
                reason: 'Accepted the final proposal because it meets the seller minimum.',
            );

            $this->complete($session, $transaction, $finalBuyerPrice, $finalBuyerDelivery, 4);

            return;
        }

        $this->deadlock($session, $transaction, $buyerMax, $deliveryWindow);
    }

    /**
     * @return array{action: string, price_minor: int, delivery_days: int, reason: string}|null
     */
    private function buyerProposal(
        NegotiationSession $session,
        Transaction $transaction,
        int $buyerMax,
        int $sellerOffer,
        int $deliveryWindow,
    ): ?array {
        $context = [
            'role' => 'buyer',
            'deal' => $transaction->title,
            'budget_minor' => $buyerMax,
            'seller_offer_minor' => $sellerOffer,
            'delivery_days' => $deliveryWindow,
            'round' => 1,
            'max_rounds' => $session->max_rounds,
        ];

        if (config('dealflow.ai_negotiation', true)) {
            $configuration = $this->agentConfiguration($transaction, 'buyer');
            $costMinor = (int) config('dealflow.ai_cost_per_call_minor', 1);

            if ($configuration !== null && ! $this->usage->canRun($configuration, $costMinor)) {
                $this->recordAgentRun(
                    $session,
                    'buyer',
                    $context,
                    null,
                    'fallback',
                    'The buyer AI budget is exhausted.',
                );

                return $this->fallbackBuyerProposal($buyerMax, $sellerOffer, $deliveryWindow);
            }

            $proposal = $this->buyerAgent->propose($context);

            if ($this->isValidProposal($proposal)) {
                $action = (string) $proposal['action'];
                $price = (int) $proposal['price_minor'];
                $days = (int) $proposal['delivery_days'];
                $reason = trim((string) $proposal['reason']);

                $valid = $days >= 1
                    && $days <= 365
                    && $price >= 1
                    && $price <= $buyerMax
                    && $reason !== ''
                    && ! ($action === 'accept' && $sellerOffer > $buyerMax);

                if ($valid) {
                    $run = $this->recordAgentRun($session, 'buyer', $context, $proposal, 'succeeded', null);
                    $this->recordUsage($run, $configuration, $context, $proposal);

                    return [
                        'action' => $action,
                        'price_minor' => $action === 'accept' ? $sellerOffer : $price,
                        'delivery_days' => $action === 'accept' ? $deliveryWindow : $days,
                        'reason' => $reason,
                    ];
                }
            }

            $run = $this->recordAgentRun(
                $session,
                'buyer',
                $context,
                $proposal,
                'fallback',
                'The AI buyer proposal was unavailable or outside the approved limits.',
            );
            $this->recordUsage($run, $configuration, $context, $proposal);
        }

        return $this->fallbackBuyerProposal($buyerMax, $sellerOffer, $deliveryWindow);
    }

    /**
     * @return array{action: string, price_minor: int, delivery_days: int, reason: string}|null
     */
    private function sellerProposal(
        NegotiationSession $session,
        Transaction $transaction,
        int $sellerOffer,
        int $sellerFloor,
        int $buyerPrice,
        int $buyerDelivery,
    ): ?array {
        $context = [
            'role' => 'seller',
            'deal' => $transaction->title,
            'seller_offer_minor' => $sellerOffer,
            'seller_floor_minor' => $sellerFloor,
            'buyer_price_minor' => $buyerPrice,
            'delivery_days' => $buyerDelivery,
            'round' => 2,
            'max_rounds' => $session->max_rounds,
        ];

        if (config('dealflow.ai_negotiation', true)) {
            $configuration = $this->agentConfiguration($transaction, 'seller');
            $costMinor = (int) config('dealflow.ai_cost_per_call_minor', 1);

            if ($configuration !== null && ! $this->usage->canRun($configuration, $costMinor)) {
                $this->recordAgentRun(
                    $session,
                    'seller',
                    $context,
                    null,
                    'fallback',
                    'The seller AI budget is exhausted.',
                );

                return $this->fallbackSellerProposal($sellerFloor, $buyerPrice, $buyerDelivery);
            }

            $proposal = $this->sellerAgent->respond($context);

            if ($this->isValidProposal($proposal)) {
                $action = (string) $proposal['action'];
                $price = (int) $proposal['price_minor'];
                $days = (int) $proposal['delivery_days'];
                $reason = $this->redactFloor(trim((string) $proposal['reason']), $sellerFloor);

                $valid = $days >= 1
                    && $days <= 365
                    && $price >= $sellerFloor
                    && $price <= $sellerOffer
                    && $reason !== ''
                    && ! ($action === 'accept' && $buyerPrice < $sellerFloor);

                if ($valid) {
                    $run = $this->recordAgentRun($session, 'seller', $context, $proposal, 'succeeded', null);
                    $this->recordUsage($run, $configuration, $context, $proposal);

                    return [
                        'action' => $action,
                        'price_minor' => $action === 'accept' ? $buyerPrice : $price,
                        'delivery_days' => $action === 'accept' ? $buyerDelivery : $days,
                        'reason' => $reason,
                    ];
                }
            }

            $run = $this->recordAgentRun(
                $session,
                'seller',
                $context,
                $proposal,
                'fallback',
                'The AI seller proposal was unavailable or outside the approved limits.',
            );
            $this->recordUsage($run, $configuration, $context, $proposal);
        }

        return $this->fallbackSellerProposal(
            $sellerFloor,
            $buyerPrice,
            $buyerDelivery,
        );
    }

    /**
     * @return array{action: string, price_minor: int, delivery_days: int, reason: string}|null
     */
    private function buyerFollowUpProposal(
        NegotiationSession $session,
        Transaction $transaction,
        int $buyerMax,
        int $sellerOffer,
        int $sellerCounterPrice,
        int $deliveryWindow,
    ): ?array {
        $context = [
            'role' => 'buyer',
            'deal' => $transaction->title,
            'budget_minor' => $buyerMax,
            'seller_offer_minor' => $sellerOffer,
            'seller_counter_price_minor' => $sellerCounterPrice,
            'delivery_days' => $deliveryWindow,
            'round' => 3,
            'max_rounds' => $session->max_rounds,
            'final_round' => true,
        ];

        if (config('dealflow.ai_negotiation', true)) {
            $configuration = $this->agentConfiguration($transaction, 'buyer');
            $costMinor = (int) config('dealflow.ai_cost_per_call_minor', 1);

            if ($configuration !== null && ! $this->usage->canRun($configuration, $costMinor)) {
                $this->recordAgentRun(
                    $session,
                    'buyer',
                    $context,
                    null,
                    'fallback',
                    'The buyer AI budget is exhausted.',
                );

                return [
                    'action' => 'counteroffer',
                    'price_minor' => $buyerMax,
                    'delivery_days' => $deliveryWindow,
                    'reason' => 'Cannot exceed the approved budget; this is the final proposal.',
                ];
            }

            $proposal = $this->buyerAgent->propose($context);

            if ($this->isValidProposal($proposal)) {
                $action = (string) $proposal['action'];
                $price = (int) $proposal['price_minor'];
                $days = (int) $proposal['delivery_days'];
                $reason = trim((string) $proposal['reason']);

                $valid = $action !== 'accept'
                    && $days >= 1
                    && $days <= 365
                    && $price >= 1
                    && $price <= $buyerMax
                    && $reason !== '';

                if ($valid) {
                    $run = $this->recordAgentRun($session, 'buyer', $context, $proposal, 'succeeded', null);
                    $this->recordUsage($run, $configuration, $context, $proposal);

                    return [
                        'action' => $action,
                        'price_minor' => $price,
                        'delivery_days' => $days,
                        'reason' => $reason,
                    ];
                }
            }

            $run = $this->recordAgentRun(
                $session,
                'buyer',
                $context,
                $proposal,
                'fallback',
                'The AI buyer follow-up was unavailable or outside the approved limits.',
            );
            $this->recordUsage($run, $configuration, $context, $proposal);
        }

        return [
            'action' => 'counteroffer',
            'price_minor' => $buyerMax,
            'delivery_days' => $deliveryWindow,
            'reason' => 'Cannot exceed the approved budget; this is the final proposal.',
        ];
    }

    /**
     * @return array{action: string, price_minor: int, delivery_days: int, reason: string}
     */
    private function fallbackBuyerProposal(int $buyerMax, int $sellerOffer, int $deliveryWindow): array
    {
        if ($sellerOffer <= $buyerMax) {
            return [
                'action' => 'accept',
                'price_minor' => $sellerOffer,
                'delivery_days' => $deliveryWindow,
                'reason' => 'Accepted the seller terms because they fit inside the approved budget.',
            ];
        }

        return [
            'action' => 'counteroffer',
            'price_minor' => $buyerMax,
            'delivery_days' => $deliveryWindow,
            'reason' => 'Countered at the approved budget maximum while keeping the delivery window.',
        ];
    }

    /**
     * @return array{action: string, price_minor: int, delivery_days: int, reason: string}
     */
    private function fallbackSellerProposal(
        int $sellerFloor,
        int $buyerPrice,
        int $buyerDelivery,
    ): array {
        if ($buyerPrice >= $sellerFloor) {
            return [
                'action' => 'accept',
                'price_minor' => $buyerPrice,
                'delivery_days' => $buyerDelivery,
                'reason' => 'Accepted the buyer proposal because it meets the seller minimum.',
            ];
        }

        return [
            'action' => 'counteroffer',
            'price_minor' => $sellerFloor,
            'delivery_days' => $buyerDelivery,
            'reason' => 'Countered at the seller minimum while keeping the delivery window.',
        ];
    }

    private function complete(
        NegotiationSession $session,
        Transaction $transaction,
        int $priceMinor,
        int $deliveryDays,
        int $round,
    ): void {
        $agreement = $this->agreements->create($transaction, $priceMinor, $deliveryDays, [
            'rounds' => $round,
            'final_price_minor' => $priceMinor,
            'final_delivery_days' => $deliveryDays,
        ]);

        Notification::send(
            [$transaction->buyer, $transaction->seller],
            new TermsReadyForApproval($agreement),
        );

        $session->update([
            'status' => NegotiationStatus::Agreed,
            'round' => $round,
            'completed_at' => now(),
        ]);

        $this->audit->record(null, $transaction, 'negotiation.agreed', [
            'final_price_minor' => $priceMinor,
            'delivery_days' => $deliveryDays,
        ]);
    }

    private function deadlock(
        NegotiationSession $session,
        Transaction $transaction,
        int $buyerMax,
        int $deliveryWindow,
    ): void {
        $session->messages()->create([
            'agent' => 'buyer',
            'round' => 1,
            'action' => 'counteroffer',
            'price_minor' => $buyerMax,
            'currency' => $transaction->currency,
            'delivery_days' => $deliveryWindow,
            'reason' => 'The approved budget is the maximum available for this deal.',
            'payload' => [
                'price_minor' => $buyerMax,
                'delivery_days' => $deliveryWindow,
            ],
        ]);

        $session->messages()->create([
            'agent' => 'seller',
            'round' => 2,
            'action' => 'reject',
            'price_minor' => null,
            'currency' => null,
            'delivery_days' => null,
            'reason' => 'The current budget is below the minimum acceptable terms.',
            'payload' => ['deadlock' => true],
        ]);

        $session->update([
            'status' => NegotiationStatus::Deadlocked,
            'round' => 2,
            'completed_at' => now(),
        ]);

        if ($transaction->state === TransactionState::Negotiating) {
            $transaction->transitionTo(TransactionState::Failed);
        }

        $this->audit->record(null, $transaction, 'negotiation.deadlocked', [
            'buyer_max_minor' => $buyerMax,
        ]);
    }

    private function storeMessage(
        NegotiationSession $session,
        string $agent,
        int $round,
        string $action,
        ?int $priceMinor,
        ?string $currency,
        ?int $deliveryDays,
        ?string $reason,
        array $payload = [],
    ): void {
        $session->messages()->create([
            'agent' => $agent,
            'round' => $round,
            'action' => $action,
            'price_minor' => $priceMinor,
            'currency' => $currency,
            'delivery_days' => $deliveryDays,
            'reason' => $reason,
            'payload' => $payload ?: [
                'price_minor' => $priceMinor,
                'delivery_days' => $deliveryDays,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $proposal
     */
    private function isValidProposal(?array $proposal): bool
    {
        return is_array($proposal)
            && isset($proposal['action'], $proposal['price_minor'], $proposal['delivery_days'], $proposal['reason'])
            && is_string($proposal['action'])
            && in_array($proposal['action'], ['offer', 'counteroffer', 'accept', 'reject'], true)
            && is_numeric($proposal['price_minor'])
            && is_numeric($proposal['delivery_days'])
            && is_string($proposal['reason']);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $proposal
     */
    private function recordAgentRun(
        NegotiationSession $session,
        string $agent,
        array $context,
        ?array $proposal,
        string $status,
        ?string $error,
    ): AgentRun {
        unset($context['seller_floor_minor']);

        $configuration = $this->agentConfiguration(
            $session->transaction,
            $agent === 'seller' ? 'seller' : 'buyer',
        );

        return $session->agentRuns()->create([
            'agent' => $agent,
            'provider' => $configuration?->provider ?? config('ai.default'),
            'model' => $configuration?->model
                ?? config('ai.providers.gemini.models.text.default')
                ?? 'unknown',
            'status' => $status,
            'input' => $context,
            'output' => $proposal,
            'error' => $error,
        ]);
    }

    private function agentConfiguration(
        Transaction $transaction,
        string $role,
    ): ?AgentConfiguration {
        return $transaction->agentConfigurations()
            ->where('role', $role)
            ->where('status', 'active')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $proposal
     */
    private function recordUsage(
        AgentRun $run,
        ?AgentConfiguration $configuration,
        array $context,
        ?array $proposal,
    ): void {
        if ($configuration === null) {
            return;
        }

        $this->usage->record(
            $run,
            $configuration,
            (int) config('dealflow.ai_cost_per_call_minor', 1),
            $this->estimateTokens($context),
            $proposal === null ? 0 : $this->estimateTokens($proposal),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function estimateTokens(array $data): int
    {
        return max(1, (int) ceil(strlen(json_encode($data, JSON_THROW_ON_ERROR)) / 4));
    }

    private function redactFloor(string $reason, int $floorMinor): string
    {
        $values = array_unique([
            (string) $floorMinor,
            number_format($floorMinor / 100, 2, '.', ''),
            number_format($floorMinor / 100, 0),
        ]);

        foreach ($values as $value) {
            if ($value !== '' && $value !== '0') {
                $reason = str_replace($value, 'the seller minimum', $reason);
            }
        }

        return trim($reason);
    }
}
