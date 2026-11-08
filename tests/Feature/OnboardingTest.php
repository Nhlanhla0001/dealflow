<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WizardDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_is_sent_to_onboarding_and_can_finish_it(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('onboarding/wizard')
                ->where('draft.wizard_type', 'onboarding'));

        $draft = WizardDraft::query()->sole();

        $steps = [
            'role' => ['role' => 'both'],
            'profile' => [
                'headline' => 'Laravel developer',
                'bio' => 'I build product workflows.',
                'timezone' => 'Africa/Johannesburg',
            ],
            'preferences' => [
                'notification_email' => true,
                'notification_in_app' => false,
            ],
        ];

        foreach ($steps as $step => $data) {
            $this->actingAs($user)
                ->postJson(route('wizard-drafts.step', $draft), [
                    'step' => $step,
                    'data' => $data,
                    'version' => $draft->fresh()->version,
                ])
                ->assertOk();

            $draft->refresh();
        }

        $this->actingAs($user)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'review',
                'data' => [],
                'version' => $draft->version,
            ])
            ->assertOk();

        $draft->refresh();

        $this->actingAs($user)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->version,
            ])
            ->assertCreated()
            ->assertJsonPath('type', 'user')
            ->assertJsonPath('redirect', route('dashboard'));

        $user->refresh();

        $this->assertSame('both', $user->role->value);
        $this->assertSame('Laravel developer', $user->headline);
        $this->assertSame('Africa/Johannesburg', $user->timezone);
        $this->assertFalse($user->notification_in_app);
        $this->assertNotNull($user->onboarding_completed_at);
    }

    public function test_a_completed_user_is_redirected_away_from_onboarding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('onboarding.show'))
            ->assertRedirect(route('dashboard'));
    }
}
