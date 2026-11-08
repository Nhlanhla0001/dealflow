<?php

namespace App\Support\Wizard;

use App\Enums\OfferStatus;
use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\WizardDraft;
use App\Notifications\BuyerRequestReceived;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BuyerRequestWizard implements WizardDefinition
{
    public function type(): string
    {
        return 'buyer_request';
    }

    public function title(): string
    {
        return 'Request a service';
    }

    public function description(): string
    {
        return 'Describe the outcome you need, set your private budget, and choose how the terms should be negotiated.';
    }

    /**
     * @return array<int, WizardStep>
     */
    public function steps(): array
    {
        return [
            new WizardStep(
                key: 'service',
                label: 'Service',
                description: 'Choose the published service you want to request.',
                rules: [
                    'service_id' => [
                        'required',
                        'integer',
                        Rule::exists('services', 'id')
                            ->where('status', ServiceStatus::Published->value),
                    ],
                ],
            ),
            new WizardStep(
                key: 'outcome',
                label: 'Outcome',
                description: 'Describe what a successful result looks like.',
                rules: [
                    'title' => ['required', 'string', 'max:120'],
                    'description' => ['required', 'string', 'max:2000'],
                    'required_deliverables' => ['required', 'array', 'min:1'],
                    'required_deliverables.*' => ['required', 'string', 'max:200'],
                ],
            ),
            new WizardStep(
                key: 'budget',
                label: 'Budget',
                description: 'Set the maximum you are willing to pay and your preferred delivery.',
                rules: [
                    'budget' => ['required', 'numeric', 'min:5', 'max:1000000'],
                    'currency' => ['required', Rule::in(['USD'])],
                    'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
                ],
            ),
            new WizardStep(
                key: 'conditions',
                label: 'Conditions',
                description: 'Separate the essentials from the points you can negotiate.',
                rules: [
                    'essential_requirements' => ['nullable', 'string', 'max:3000'],
                    'negotiable_conditions' => ['nullable', 'string', 'max:3000'],
                ],
            ),
            new WizardStep(
                key: 'negotiation',
                label: 'Negotiation',
                description: 'Choose manual negotiation or independent AI agents.',
                rules: [
                    'negotiation_mode' => ['required', Rule::in(['manual', 'ai'])],
                ],
            ),
            new WizardStep(
                key: 'review',
                label: 'Review',
                description: 'Check the request before it is sent.',
                review: true,
            ),
            new WizardStep(
                key: 'publish',
                label: 'Confirm',
                description: 'Confirm the request and notify the seller.',
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
            'currency' => 'USD',
            'negotiation_mode' => 'ai',
            'required_deliverables' => [],
        ];
    }

    public function finalize(WizardDraft $draft): Transaction
    {
        $data = $draft->data ?? [];
        $completed = $this->completedSteps($data);
        $required = ['service', 'outcome', 'budget', 'conditions', 'negotiation', 'review'];

        if (array_diff($required, $completed) !== []) {
            throw ValidationException::withMessages([
                'wizard' => 'Complete every step before sending the request.',
            ]);
        }

        $service = Service::query()
            ->where('status', ServiceStatus::Published->value)
            ->with('seller')
            ->findOrFail((int) $data['service_id']);

        $transaction = Transaction::create([
            'buyer_id' => $draft->user_id,
            'seller_id' => $service->seller_id,
            'service_id' => $service->id,
            'title' => (string) $data['title'],
            'description' => (string) $data['description'],
            'currency' => (string) ($data['currency'] ?? 'USD'),
            'budget_minor' => (int) round(((float) $data['budget']) * 100),
            'delivery_days' => (int) $data['delivery_days'],
            'negotiation_mode' => (string) ($data['negotiation_mode'] ?? 'ai'),
            'state' => 'draft',
        ]);

        $deliverables = array_values(array_filter($data['required_deliverables'] ?? []));
        $terms = "Service: {$service->title}\n";
        $terms .= 'Buyer deliverables: '.implode(', ', $deliverables)."\n";

        if (($data['essential_requirements'] ?? '') !== '') {
            $terms .= "Essential requirements: {$data['essential_requirements']}\n";
        }

        if (($data['negotiable_conditions'] ?? '') !== '') {
            $terms .= "Negotiable conditions: {$data['negotiable_conditions']}";
        }

        $transaction->offer()->create([
            'seller_id' => $service->seller_id,
            'price_minor' => $service->price_minor,
            'currency' => $service->currency,
            'delivery_days' => $service->delivery_days,
            'terms' => $terms,
            'status' => OfferStatus::Active,
        ]);

        $transaction->update([
            'seller_offer_minor' => $service->price_minor,
            'seller_delivery_days' => $service->delivery_days,
        ]);

        $service->seller?->notify(new BuyerRequestReceived($transaction));

        return $transaction->fresh(['seller', 'buyer', 'offer']);
    }

    /**
     * @param  array<int, string>  $completed
     */
    private function allRequiredStepsComplete(array $completed): bool
    {
        return array_diff(
            ['service', 'outcome', 'budget', 'conditions', 'negotiation'],
            $completed,
        ) === [];
    }
}
