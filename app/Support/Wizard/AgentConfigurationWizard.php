<?php

namespace App\Support\Wizard;

use App\Models\AgentConfiguration;
use App\Models\Transaction;
use App\Models\WizardDraft;
use App\Services\AiCreditService;
use App\Support\Ai\ProviderCatalog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AgentConfigurationWizard implements WizardDefinition
{
    public function __construct(
        private readonly ProviderCatalog $providers,
        private readonly AiCreditService $credits,
    ) {}

    public function type(): string
    {
        return 'agent_config';
    }

    public function title(): string
    {
        return 'Configure your agent';
    }

    public function description(): string
    {
        return 'Set the objective, private constraints, turn limit, and AI budget for your side of this negotiation.';
    }

    /**
     * @return array<int, WizardStep>
     */
    public function steps(): array
    {
        return [
            new WizardStep(
                key: 'provider',
                label: 'Provider',
                description: 'Choose an available AI provider and model.',
                rules: [
                    'provider' => ['required', Rule::in(array_keys($this->providers->providers()))],
                    'model' => ['required', 'string', 'max:120'],
                ],
            ),
            new WizardStep(
                key: 'objective',
                label: 'Objective',
                description: 'Tell the agent what a good outcome looks like.',
                rules: [
                    'objective' => ['required', 'string', 'max:500'],
                ],
            ),
            new WizardStep(
                key: 'constraints',
                label: 'Private constraints',
                description: 'These limits are enforced server-side and never shown to the other side.',
                rules: [],
            ),
            new WizardStep(
                key: 'limits',
                label: 'Limits',
                description: 'Bound the negotiation turns and the AI spend.',
                rules: [
                    'max_turns' => ['required', 'integer', 'min:1', 'max:10'],
                    'spending_limit' => ['required', 'numeric', 'min:0', 'max:1000'],
                    'billing_method' => ['required', Rule::in(['prepaid_credits'])],
                ],
            ),
            new WizardStep(
                key: 'review',
                label: 'Review',
                description: 'Review permissions, privacy, and estimated usage.',
                review: true,
            ),
            new WizardStep(
                key: 'publish',
                label: 'Activate',
                description: 'Activate the agent for this negotiation.',
                review: true,
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function stepKeys(): array
    {
        return array_map(fn (WizardStep $step): string => $step->key, $this->steps());
    }

    public function step(string $key): WizardStep
    {
        foreach ($this->steps() as $step) {
            if ($step->key === $key) {
                return $step;
            }
        }

        throw ValidationException::withMessages([
            'step' => 'This wizard step does not exist.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateStep(string $key, array $data): array
    {
        $step = $this->step($key);

        if ($key === 'constraints') {
            $isSeller = ($data['role'] ?? 'buyer') === 'seller';
            $rules = $isSeller
                ? [
                    'floor' => ['required', 'numeric', 'min:1', 'max:1000000'],
                    'max_delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
                ]
                : [
                    'max_budget' => ['required', 'numeric', 'min:1', 'max:1000000'],
                    'max_delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
                ];

            return Validator::make($data, $rules)->validate();
        }

        if ($step->rules === []) {
            return [];
        }

        return Validator::make($data, $step->rules)->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function completedSteps(array $data): array
    {
        $completed = [];

        foreach ($this->steps() as $step) {
            if ($step->key === 'publish') {
                continue;
            }

            if ($step->key === 'review') {
                if ($this->allRequiredStepsComplete($completed)) {
                    $completed[] = $step->key;
                }

                continue;
            }

            try {
                $this->validateStep($step->key, $data);
                $completed[] = $step->key;
            } catch (ValidationException) {
                break;
            }
        }

        return $completed;
    }

    /**
     * @return array<string, mixed>
     */
    public function startData(): array
    {
        return [
            'role' => 'buyer',
            'provider' => 'gemini',
            'model' => $this->providers->defaultModel('gemini'),
            'objective' => '',
            'max_budget' => null,
            'floor' => null,
            'max_delivery_days' => 7,
            'max_turns' => 5,
            'spending_limit' => 1,
            'billing_method' => 'prepaid_credits',
        ];
    }

    public function finalize(WizardDraft $draft): AgentConfiguration
    {
        $data = $draft->data ?? [];
        $completed = $this->completedSteps($data);
        $required = ['provider', 'objective', 'constraints', 'limits', 'review'];

        if (array_diff($required, $completed) !== []) {
            throw ValidationException::withMessages([
                'wizard' => 'Complete every step before activating the agent.',
            ]);
        }

        $transaction = Transaction::query()->findOrFail($draft->context_id);
        $role = (string) ($data['role'] ?? 'buyer');

        $isParty = match ($role) {
            'buyer' => $transaction->buyer_id === $draft->user_id,
            'seller' => $transaction->seller_id === $draft->user_id,
            default => false,
        };

        if (! $isParty) {
            throw ValidationException::withMessages([
                'role' => 'You are not a party to this transaction in that role.',
            ]);
        }

        $provider = (string) $data['provider'];
        $model = (string) $data['model'];

        if (! $this->providers->isConfigured($provider)) {
            throw ValidationException::withMessages([
                'provider' => 'This provider is not configured. Add its API key before activating.',
            ]);
        }

        if (! $this->providers->supports($provider, $model)) {
            throw ValidationException::withMessages([
                'model' => 'This model is not available for the selected provider.',
            ]);
        }

        $maxTurns = (int) $data['max_turns'];
        $spendingLimitMinor = (int) round(((float) $data['spending_limit']) * 100);

        if (! $this->credits->canSpend($draft->user, $spendingLimitMinor)) {
            throw ValidationException::withMessages([
                'spending_limit' => 'The AI credit balance is too low for this spending limit.',
            ]);
        }

        $constraints = [
            'max_delivery_days' => (int) $data['max_delivery_days'],
        ];

        if ($role === 'seller') {
            $floorMinor = (int) round(((float) $data['floor']) * 100);
            $asking = (int) ($transaction->seller_offer_minor ?? $transaction->offer?->price_minor ?? 0);

            if ($asking > 0 && $floorMinor > $asking) {
                throw ValidationException::withMessages([
                    'floor' => 'Your private floor cannot be above your opening offer.',
                ]);
            }

            $constraints['floor_minor'] = $floorMinor;
            $transaction->update(['seller_floor_minor' => $floorMinor]);
        } else {
            $maxBudgetMinor = (int) round(((float) $data['max_budget']) * 100);

            if ($maxBudgetMinor > $transaction->budget_minor) {
                throw ValidationException::withMessages([
                    'max_budget' => 'Your agent budget cannot exceed the transaction budget.',
                ]);
            }

            $constraints['max_budget_minor'] = $maxBudgetMinor;
        }

        $configuration = AgentConfiguration::query()->updateOrCreate(
            [
                'transaction_id' => $transaction->id,
                'user_id' => $draft->user_id,
            ],
            [
                'role' => $role,
                'provider' => $provider,
                'model' => $model,
                'objective' => (string) $data['objective'],
                'constraints' => $constraints,
                'max_turns' => $maxTurns,
                'spending_limit_minor' => $spendingLimitMinor,
                'billing_method' => (string) $data['billing_method'],
                'status' => 'active',
                'activated_at' => now(),
            ],
        );

        return $configuration->fresh();
    }

    /**
     * @param  array<int, string>  $completed
     */
    private function allRequiredStepsComplete(array $completed): bool
    {
        return array_diff(
            ['provider', 'objective', 'constraints', 'limits'],
            $completed,
        ) === [];
    }
}
