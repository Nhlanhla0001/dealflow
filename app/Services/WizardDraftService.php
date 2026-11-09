<?php

namespace App\Services;

use App\Enums\WizardStatus;
use App\Exceptions\WizardConflictException;
use App\Exceptions\WizardExpiredException;
use App\Models\AgentConfiguration;
use App\Models\AuditLog;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WizardDraft;
use App\Support\Wizard\WizardDefinition;
use App\Support\Wizard\WizardPublishResult;
use App\Support\Wizard\WizardRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WizardDraftService
{
    public function __construct(private readonly WizardRegistry $registry) {}

    public function start(User $user, string $wizardType, ?Model $context = null): WizardDraft
    {
        $definition = $this->registry->for($wizardType);

        $query = WizardDraft::query()
            ->where('user_id', $user->id)
            ->where('wizard_type', $wizardType)
            ->usable();

        if ($context instanceof Model) {
            $query->where('context_type', $context::class)
                ->where('context_id', $context->getKey());
        } else {
            $query->whereNull('context_type')->whereNull('context_id');
        }

        $existing = $query->latest('updated_at')->first();

        if ($existing instanceof WizardDraft) {
            return $existing;
        }

        return WizardDraft::create([
            'user_id' => $user->id,
            'wizard_type' => $wizardType,
            'context_type' => $context instanceof Model ? $context::class : null,
            'context_id' => $context instanceof Model ? $context->getKey() : null,
            'current_step' => $definition->stepKeys()[0],
            'data' => $definition->startData(),
            'completed_steps' => [],
            'version' => 1,
            'status' => WizardStatus::Draft,
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function startForService(User $user, Service $service): WizardDraft
    {
        $draft = $this->start($user, 'seller_listing', $service);

        if ($draft->wasRecentlyCreated) {
            $definition = $this->registry->for('seller_listing');
            $data = $this->dataFromService($service);
            $completed = $definition->completedSteps($data);

            $draft->update([
                'data' => $data,
                'completed_steps' => $completed,
                'current_step' => 'preview',
            ]);
        }

        return $draft->fresh();
    }

    public function startForOnboarding(User $user): WizardDraft
    {
        $draft = $this->start($user, 'onboarding');

        if ($draft->wasRecentlyCreated) {
            $definition = $this->registry->for('onboarding');
            $data = [
                'role' => $user->role->value,
                'headline' => $user->headline,
                'bio' => $user->bio,
                'timezone' => null,
                'notification_email' => null,
                'notification_in_app' => null,
            ];

            $draft->update([
                'data' => $data,
                'completed_steps' => $definition->completedSteps($data),
                'current_step' => 'role',
            ]);
        }

        return $draft->fresh();
    }

    public function startForBuyerRequest(User $user, Service $service): WizardDraft
    {
        $draft = $this->start($user, 'buyer_request', $service);

        if ($draft->wasRecentlyCreated) {
            $data = [
                'service_id' => $service->id,
                'title' => $service->title,
                'description' => '',
                'required_deliverables' => $service->deliverables,
                'budget' => $service->price_minor / 100,
                'currency' => $service->currency,
                'delivery_days' => $service->delivery_days,
                'negotiation_mode' => $service->negotiation_enabled ? 'ai' : 'manual',
            ];

            $definition = $this->registry->for('buyer_request');
            $completed = $definition->completedSteps($data);

            $draft->update([
                'data' => $data,
                'completed_steps' => $completed,
                'current_step' => 'outcome',
            ]);
        }

        return $draft->fresh();
    }

    public function startForAgent(User $user, Transaction $transaction, string $role): WizardDraft
    {
        $draft = $this->start($user, 'agent_config', $transaction);

        if ($draft->wasRecentlyCreated) {
            $definition = $this->registry->for('agent_config');
            $data = $definition->startData();
            $data['role'] = $role;

            if ($role === 'buyer') {
                $data['max_budget'] = $transaction->budget_minor / 100;
                $data['objective'] = 'Negotiate the best terms inside my private budget.';
            } else {
                $opening = $transaction->seller_floor_minor
                    ?? $transaction->seller_offer_minor
                    ?? $transaction->offer()->value('price_minor')
                    ?? $transaction->budget_minor;
                $data['floor'] = $opening / 100;
                $data['objective'] = 'Protect my private floor, delivery capacity, and scope.';
            }

            $data['max_delivery_days'] = $transaction->delivery_days;

            $draft->update([
                'data' => $data,
                'completed_steps' => $definition->completedSteps($data),
                'current_step' => 'provider',
            ]);
        }

        return $draft->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveDraft(WizardDraft $draft, array $data, string $currentStep, int $version): WizardDraft
    {
        return DB::transaction(function () use ($draft, $data, $currentStep, $version): WizardDraft {
            $fresh = $this->lockedDraft($draft, $version);
            $definition = $this->registry->for($fresh->wizard_type);

            $merged = array_replace($fresh->data ?? [], $data);
            $completed = $definition->completedSteps($merged);
            $allowedStep = $this->nextStep($definition, $completed);
            $keys = $definition->stepKeys();
            $requestedIndex = array_search($currentStep, $keys, true);
            $allowedIndex = array_search($allowedStep, $keys, true);
            $resolvedStep = $requestedIndex !== false
                && $allowedIndex !== false
                && $requestedIndex <= $allowedIndex
                    ? $currentStep
                    : $allowedStep;

            $fresh->update([
                'data' => $merged,
                'completed_steps' => $completed,
                'current_step' => $resolvedStep,
                'version' => $fresh->version + 1,
            ]);

            return $fresh->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveStep(WizardDraft $draft, string $stepKey, array $data, int $version): WizardDraft
    {
        return DB::transaction(function () use ($draft, $stepKey, $data, $version): WizardDraft {
            $fresh = $this->lockedDraft($draft, $version);
            $definition = $this->registry->for($fresh->wizard_type);

            if ($stepKey === 'publish') {
                throw ValidationException::withMessages([
                    'step' => 'Use the publish action to finish this wizard.',
                ]);
            }

            $step = $definition->step($stepKey);

            if ($stepKey !== $fresh->current_step && ! in_array($stepKey, $fresh->completed_steps ?? [], true)) {
                throw ValidationException::withMessages([
                    'step' => 'Complete the previous steps before continuing.',
                ]);
            }

            $mergedForValidation = array_replace($fresh->data ?? [], $data);
            $validated = $definition->validateStep($stepKey, $mergedForValidation);
            $merged = array_replace($fresh->data ?? [], $validated);
            $completed = $definition->completedSteps($merged);
            $keys = $definition->stepKeys();
            $index = array_search($stepKey, $keys, true);

            if ($index === false) {
                throw ValidationException::withMessages([
                    'step' => 'This wizard step does not exist.',
                ]);
            }

            if ($stepKey !== $fresh->current_step) {
                $completed = array_values(array_filter(
                    $completed,
                    fn (string $key): bool => (array_search($key, $keys, true) ?: 0) <= $index,
                ));
            }

            $fresh->update([
                'data' => $merged,
                'completed_steps' => $completed,
                'current_step' => $this->stepAfter($definition, $stepKey),
                'version' => $fresh->version + 1,
            ]);

            return $fresh->fresh();
        });
    }

    public function publish(WizardDraft $draft, int $version): WizardPublishResult
    {
        return DB::transaction(function () use ($draft, $version): WizardPublishResult {
            $fresh = $this->lockedDraft($draft, $version);
            $definition = $this->registry->for($fresh->wizard_type);
            $result = $definition->finalize($fresh);

            if ($result instanceof Service) {
                $publish = new WizardPublishResult(
                    type: 'service',
                    id: $result->id,
                    url: route('services.show', $result),
                    meta: ['slug' => $result->slug],
                );
                $action = 'service.published';
                $transactionId = null;
            } elseif ($result instanceof Transaction) {
                $publish = new WizardPublishResult(
                    type: 'transaction',
                    id: $result->id,
                    url: route('transactions.show', $result),
                    meta: ['title' => $result->title],
                );
                $action = 'buyer_request.created';
                $transactionId = $result->id;
            } elseif ($result instanceof AgentConfiguration) {
                $publish = new WizardPublishResult(
                    type: 'agent',
                    id: $result->id,
                    url: route('transactions.show', $result->transaction_id),
                    meta: ['role' => $result->role],
                );
                $action = 'agent.activated';
                $transactionId = $result->transaction_id;
            } elseif ($result instanceof User) {
                $publish = new WizardPublishResult(
                    type: 'user',
                    id: $result->id,
                    url: $result->hasVerifiedEmail()
                        ? route('dashboard')
                        : route('verification.notice'),
                    meta: ['name' => $result->name],
                );
                $action = 'onboarding.completed';
                $transactionId = null;
            } else {
                throw ValidationException::withMessages([
                    'wizard' => 'This wizard did not return a publishable result.',
                ]);
            }

            $fresh->update([
                'status' => WizardStatus::Completed,
                'current_step' => 'publish',
                'version' => $fresh->version + 1,
            ]);

            AuditLog::create([
                'user_id' => $fresh->user_id,
                'transaction_id' => $transactionId,
                'action' => $action,
                'metadata' => [
                    'wizard_type' => $fresh->wizard_type,
                    'result_type' => $publish->type,
                    'result_id' => $publish->id,
                ],
            ]);

            return $publish;
        });
    }

    public function abandon(WizardDraft $draft, int $version): WizardDraft
    {
        return DB::transaction(function () use ($draft, $version): WizardDraft {
            $fresh = WizardDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($fresh->version !== $version) {
                throw new WizardConflictException($fresh);
            }

            $fresh->update([
                'status' => WizardStatus::Abandoned,
                'version' => $fresh->version + 1,
            ]);

            return $fresh->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(WizardDraft $draft): array
    {
        $definition = $this->registry->for($draft->wizard_type);

        return [
            'uuid' => $draft->uuid,
            'wizard_type' => $draft->wizard_type,
            'title' => $definition->title(),
            'description' => $definition->description(),
            'current_step' => $draft->current_step,
            'data' => $draft->data ?? [],
            'completed_steps' => $draft->completed_steps ?? [],
            'version' => $draft->version,
            'status' => $draft->status->value,
            'expires_at' => $draft->expires_at?->toIso8601String(),
            'context_type' => $draft->context_type,
            'context_id' => $draft->context_id,
            'steps' => array_map(
                fn ($step): array => $step->toArray(),
                $definition->steps(),
            ),
        ];
    }

    private function lockedDraft(WizardDraft $draft, int $version): WizardDraft
    {
        $fresh = WizardDraft::query()->lockForUpdate()->findOrFail($draft->id);

        if ($fresh->isExpired()) {
            throw new WizardExpiredException;
        }

        if ($fresh->status !== WizardStatus::Draft) {
            throw ValidationException::withMessages([
                'draft' => 'This wizard draft is no longer editable.',
            ]);
        }

        if ($fresh->version !== $version) {
            throw new WizardConflictException($fresh);
        }

        return $fresh;
    }

    private function stepAfter(WizardDefinition $definition, string $stepKey): string
    {
        $keys = $definition->stepKeys();
        $index = array_search($stepKey, $keys, true);

        if ($index === false || $index >= count($keys) - 1) {
            return 'publish';
        }

        return $keys[$index + 1];
    }

    /**
     * @param  array<int, string>  $completed
     */
    private function nextStep(WizardDefinition $definition, array $completed): string
    {
        foreach ($definition->stepKeys() as $key) {
            if ($key === 'publish') {
                continue;
            }

            if (! in_array($key, $completed, true)) {
                return $key;
            }
        }

        return 'publish';
    }

    /**
     * @return array<string, mixed>
     */
    private function dataFromService(Service $service): array
    {
        return [
            'category_id' => $service->category_id,
            'title' => $service->title,
            'description' => $service->description,
            'deliverables' => $service->deliverables,
            'pricing_type' => $service->pricing_type->value,
            'price' => $service->price_minor / 100,
            'currency' => $service->currency,
            'includes' => $service->includes,
            'delivery_days' => $service->delivery_days,
            'revisions' => $service->revisions,
            'exclusions' => $service->exclusions,
            'requirements' => $service->requirements,
            'image_urls' => $service->images ?? [],
            'supporting_info' => $service->supporting_info,
            'negotiation_enabled' => $service->negotiation_enabled,
        ];
    }
}
