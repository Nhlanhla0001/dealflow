<?php

namespace App\Support\Wizard;

use App\Models\User;
use App\Models\WizardDraft;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OnboardingWizard implements WizardDefinition
{
    public function type(): string
    {
        return 'onboarding';
    }

    public function title(): string
    {
        return 'Finish your DealFlow profile';
    }

    public function description(): string
    {
        return 'A few details so buyers and sellers know who they are working with. You can change these later.';
    }

    /**
     * @return array<int, WizardStep>
     */
    public function steps(): array
    {
        return [
            new WizardStep(
                key: 'role',
                label: 'Role',
                description: 'How will you use DealFlow?',
                rules: [
                    'role' => ['required', Rule::in(['buyer', 'seller', 'both'])],
                ],
            ),
            new WizardStep(
                key: 'profile',
                label: 'Profile',
                description: 'Add a short headline and bio.',
                rules: [
                    'headline' => ['nullable', 'string', 'max:120'],
                    'bio' => ['nullable', 'string', 'max:1000'],
                    'timezone' => ['required', 'timezone'],
                ],
            ),
            new WizardStep(
                key: 'preferences',
                label: 'Notifications',
                description: 'Choose how you want to hear about requests and approvals.',
                rules: [
                    'notification_email' => ['required', 'boolean'],
                    'notification_in_app' => ['required', 'boolean'],
                ],
            ),
            new WizardStep(
                key: 'review',
                label: 'Review',
                description: 'Check your profile before finishing.',
                review: true,
            ),
            new WizardStep(
                key: 'publish',
                label: 'Finish',
                description: 'Confirm and complete onboarding.',
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
                if (array_diff(['role', 'profile', 'preferences'], $completed) === []) {
                    $completed[] = 'review';
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
            'headline' => null,
            'bio' => null,
            'timezone' => null,
            'notification_email' => null,
            'notification_in_app' => null,
        ];
    }

    public function finalize(WizardDraft $draft): User
    {
        $data = $draft->data ?? [];
        $completed = $this->completedSteps($data);

        if (array_diff(['role', 'profile', 'preferences', 'review'], $completed) !== []) {
            throw ValidationException::withMessages([
                'wizard' => 'Complete every step before finishing onboarding.',
            ]);
        }

        $user = User::query()->findOrFail($draft->user_id);
        $user->update([
            'role' => (string) $data['role'],
            'headline' => $data['headline'] ?? null,
            'bio' => $data['bio'] ?? null,
            'timezone' => (string) $data['timezone'],
            'notification_email' => (bool) $data['notification_email'],
            'notification_in_app' => (bool) $data['notification_in_app'],
            'onboarding_completed_at' => now(),
        ]);

        return $user->fresh();
    }
}
