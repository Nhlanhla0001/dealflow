<?php

namespace App\Support\Wizard;

use App\Enums\PricingType;
use App\Models\Service;
use App\Models\WizardDraft;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SellerListingWizard implements WizardDefinition
{
    public function type(): string
    {
        return 'seller_listing';
    }

    public function title(): string
    {
        return 'Publish a service';
    }

    public function description(): string
    {
        return 'Turn what you do into a clear, discoverable service listing. Your progress is saved as a draft.';
    }

    /**
     * @return array<int, WizardStep>
     */
    public function steps(): array
    {
        return [
            new WizardStep(
                key: 'category',
                label: 'Category',
                description: 'Where should buyers find this service?',
                rules: [
                    'category_id' => [
                        'required',
                        'integer',
                        Rule::exists('categories', 'id')->where('is_active', true),
                    ],
                ],
            ),
            new WizardStep(
                key: 'basics',
                label: 'Service basics',
                description: 'Describe the outcome a buyer is paying for.',
                rules: [
                    'title' => ['required', 'string', 'max:120'],
                    'description' => ['required', 'string', 'max:5000'],
                    'deliverables' => ['required', 'array', 'min:1'],
                    'deliverables.*' => ['required', 'string', 'max:200'],
                ],
            ),
            new WizardStep(
                key: 'pricing',
                label: 'Pricing',
                description: 'Set the price and what is included.',
                rules: [
                    'pricing_type' => ['required', Rule::enum(PricingType::class)],
                    'price' => ['required', 'numeric', 'min:5', 'max:1000000'],
                    'currency' => ['required', Rule::in(['USD'])],
                    'includes' => ['nullable', 'string', 'max:2000'],
                ],
            ),
            new WizardStep(
                key: 'delivery',
                label: 'Delivery',
                description: 'Set the timeline, revisions, and requirements.',
                rules: [
                    'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
                    'revisions' => ['required', 'integer', 'min:0', 'max:20'],
                    'exclusions' => ['nullable', 'string', 'max:2000'],
                    'requirements' => ['nullable', 'string', 'max:3000'],
                ],
            ),
            new WizardStep(
                key: 'media',
                label: 'Proof and media',
                description: 'Add up to six images and supporting information.',
                rules: [
                    'image_urls' => ['nullable', 'array', 'max:3'],
                    'image_urls.*' => ['nullable', 'url', 'max:2048'],
                    'supporting_info' => ['nullable', 'string', 'max:3000'],
                ],
            ),
            new WizardStep(
                key: 'negotiation',
                label: 'Negotiation',
                description: 'Decide whether buyers may negotiate the listed terms.',
                rules: [
                    'negotiation_enabled' => ['required', 'boolean'],
                ],
            ),
            new WizardStep(
                key: 'preview',
                label: 'Preview',
                description: 'Review the listing exactly as a buyer will see it.',
                review: true,
            ),
            new WizardStep(
                key: 'publish',
                label: 'Publish',
                description: 'Confirm and publish the listing.',
                review: true,
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function stepKeys(): array
    {
        return array_map(
            fn (WizardStep $step): string => $step->key,
            $this->steps(),
        );
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

            if ($step->key === 'preview') {
                if ($this->allRequiredStepsComplete($completed)) {
                    $completed[] = $step->key;
                }

                continue;
            }

            try {
                $this->validateStep($step->key, $data);
                $completed[] = $step->key;
            } catch (ValidationException) {
                // Progress is sequential: stop at the first invalid step.
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
            'pricing_type' => PricingType::Fixed->value,
            'revisions' => 1,
            'negotiation_enabled' => true,
            'deliverables' => [],
            'image_urls' => [],
        ];
    }

    public function finalize(WizardDraft $draft): Service
    {
        $data = $draft->data ?? [];

        $completed = $this->completedSteps($data);
        $required = ['category', 'basics', 'pricing', 'delivery', 'media', 'negotiation', 'preview'];

        if (array_diff($required, $completed) !== []) {
            throw ValidationException::withMessages([
                'wizard' => 'Complete every step before publishing.',
            ]);
        }

        $sellerId = $draft->user_id;
        $slug = $this->uniqueSlug((string) $data['title'], $draft->context_id);

        $attributes = [
            'category_id' => (int) $data['category_id'],
            'title' => (string) $data['title'],
            'slug' => $slug,
            'description' => (string) $data['description'],
            'deliverables' => array_values($data['deliverables'] ?? []),
            'pricing_type' => (string) ($data['pricing_type'] ?? PricingType::Fixed->value),
            'price_minor' => (int) round(((float) $data['price']) * 100),
            'currency' => (string) ($data['currency'] ?? 'USD'),
            'includes' => $data['includes'] ?? null,
            'delivery_days' => (int) $data['delivery_days'],
            'revisions' => (int) ($data['revisions'] ?? 0),
            'exclusions' => $data['exclusions'] ?? null,
            'requirements' => $data['requirements'] ?? null,
            'images' => array_values(array_filter($data['image_urls'] ?? [])),
            'supporting_info' => $data['supporting_info'] ?? null,
            'negotiation_enabled' => (bool) ($data['negotiation_enabled'] ?? true),
            'status' => 'published',
            'published_at' => now(),
        ];

        if ($draft->context_type === Service::class && $draft->context_id !== null) {
            $service = Service::query()
                ->where('seller_id', $sellerId)
                ->findOrFail($draft->context_id);

            $service->update($attributes);

            return $service->fresh();
        }

        return Service::create([
            'seller_id' => $sellerId,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<int, string>  $completed
     */
    private function allRequiredStepsComplete(array $completed): bool
    {
        return array_diff(
            ['category', 'basics', 'pricing', 'delivery', 'media', 'negotiation'],
            $completed,
        ) === [];
    }

    private function uniqueSlug(string $title, ?int $ignoreServiceId = null): string
    {
        $base = Str::slug($title) ?: 'service';
        $slug = $base;
        $suffix = 2;

        while (
            Service::query()
                ->where('slug', $slug)
                ->when($ignoreServiceId !== null, fn ($query) => $query->whereKeyNot($ignoreServiceId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
