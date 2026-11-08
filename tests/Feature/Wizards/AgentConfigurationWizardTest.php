<?php

namespace Tests\Feature\Wizards;

use App\Enums\AgentStatus;
use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use App\Models\AgentConfiguration;
use App\Models\Category;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WizardDraft;
use App\Services\AiCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentConfigurationWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.providers.gemini.key' => 'test-gemini-key',
            'ai.providers.gemini.models.text.default' => 'gemini-3-flash-preview',
        ]);
    }

    public function test_a_buyer_can_start_an_agent_configuration(): void
    {
        [$buyer, , $transaction] = $this->context();

        $this->actingAs($buyer)
            ->get(route('agents.create', $transaction))
            ->assertRedirect();

        $draft = WizardDraft::query()->sole();

        $this->assertSame('agent_config', $draft->wizard_type);
        $this->assertSame($transaction->id, $draft->context_id);
        $this->assertSame('buyer', $draft->data['role']);
        $this->assertSame('provider', $draft->current_step);
        $this->assertSame(500.0, (float) $draft->data['max_budget']);
    }

    public function test_a_seller_can_start_an_agent_configuration(): void
    {
        [, $seller, $transaction] = $this->context();

        $this->actingAs($seller)
            ->get(route('agents.create', $transaction))
            ->assertRedirect();

        $draft = WizardDraft::query()->sole();

        $this->assertSame('seller', $draft->data['role']);
        $this->assertSame(450.0, (float) $draft->data['floor']);
    }

    public function test_a_non_party_cannot_start_an_agent_configuration(): void
    {
        [, , $transaction] = $this->context();
        $outsider = User::factory()->buyer()->create();

        $this->actingAs($outsider)
            ->get(route('agents.create', $transaction))
            ->assertForbidden();
    }

    public function test_a_buyer_agent_activates_with_private_constraints(): void
    {
        [$buyer, , $transaction] = $this->context();
        $this->credit($buyer);

        $draft = $this->startAgent($buyer, $transaction);

        $this->completeSteps($buyer, $draft, [
            'provider' => [
                'provider' => 'gemini',
                'model' => 'gemini-3-flash-preview',
            ],
            'objective' => [
                'objective' => 'Negotiate a fair price inside my budget.',
            ],
            'constraints' => [
                'max_budget' => 500,
                'max_delivery_days' => 7,
            ],
            'limits' => [
                'max_turns' => 3,
                'spending_limit' => 1,
                'billing_method' => 'prepaid_credits',
            ],
        ]);

        $this->actingAs($buyer)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->fresh()->version,
            ])
            ->assertCreated()
            ->assertJsonPath('type', 'agent')
            ->assertJsonPath('redirect', route('transactions.show', $transaction));

        $configuration = AgentConfiguration::query()->sole();

        $this->assertSame($buyer->id, $configuration->user_id);
        $this->assertSame('buyer', $configuration->role);
        $this->assertSame(AgentStatus::Active, $configuration->status);
        $this->assertSame(3, $configuration->max_turns);
        $this->assertSame(100, $configuration->spending_limit_minor);
        $this->assertSame(50000, $configuration->constraints['max_budget_minor']);
    }

    public function test_a_seller_agent_activation_updates_the_private_floor(): void
    {
        [, $seller, $transaction] = $this->context();
        $this->credit($seller);

        $draft = $this->startAgent($seller, $transaction);

        $this->completeSteps($seller, $draft, [
            'provider' => [
                'provider' => 'gemini',
                'model' => 'gemini-3-flash-preview',
            ],
            'objective' => [
                'objective' => 'Protect my floor and delivery capacity.',
            ],
            'constraints' => [
                'floor' => 460,
                'max_delivery_days' => 7,
            ],
            'limits' => [
                'max_turns' => 4,
                'spending_limit' => 1,
                'billing_method' => 'prepaid_credits',
            ],
        ]);

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->fresh()->version,
            ])
            ->assertCreated();

        $this->assertSame(46000, $transaction->fresh()->seller_floor_minor);
        $this->assertSame('seller', AgentConfiguration::query()->sole()->role);
    }

    public function test_activation_fails_when_the_credit_balance_is_too_low(): void
    {
        [$buyer, , $transaction] = $this->context();

        $draft = $this->startAgent($buyer, $transaction);

        $this->completeSteps($buyer, $draft, $this->buyerSteps(spendingLimit: 20));

        $this->actingAs($buyer)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->fresh()->version,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('spending_limit');

        $this->assertDatabaseCount('agent_configurations', 0);
    }

    public function test_activation_fails_when_the_provider_is_not_configured(): void
    {
        config(['ai.providers.gemini.key' => null]);

        [$buyer, , $transaction] = $this->context();
        $this->credit($buyer);

        $draft = $this->startAgent($buyer, $transaction);

        $this->completeSteps($buyer, $draft, $this->buyerSteps());

        $this->actingAs($buyer)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->fresh()->version,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('provider');
    }

    public function test_a_buyer_agent_cannot_exceed_the_transaction_budget(): void
    {
        [$buyer, , $transaction] = $this->context();
        $this->credit($buyer);

        $draft = $this->startAgent($buyer, $transaction);

        $this->completeSteps($buyer, $draft, $this->buyerSteps(maxBudget: 900));

        $this->actingAs($buyer)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->fresh()->version,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_budget');
    }

    private function startAgent(User $user, Transaction $transaction): WizardDraft
    {
        $this->actingAs($user)
            ->get(route('agents.create', $transaction))
            ->assertRedirect();

        return WizardDraft::query()->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, array<string, mixed>>  $steps
     */
    private function completeSteps(User $user, WizardDraft $draft, array $steps): void
    {
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
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buyerSteps(
        float $maxBudget = 500,
        float $spendingLimit = 1,
    ): array {
        return [
            'provider' => [
                'provider' => 'gemini',
                'model' => 'gemini-3-flash-preview',
            ],
            'objective' => [
                'objective' => 'Negotiate a fair price inside my budget.',
            ],
            'constraints' => [
                'max_budget' => $maxBudget,
                'max_delivery_days' => 7,
            ],
            'limits' => [
                'max_turns' => 3,
                'spending_limit' => $spendingLimit,
                'billing_method' => 'prepaid_credits',
            ],
        ];
    }

    private function credit(User $user, int $amount = 1000): void
    {
        app(AiCreditService::class)->credit(
            $user,
            $amount,
            'test-credit-'.$user->id.'-'.$amount,
            'Test credit',
        );
    }

    /**
     * @return array{0: User, 1: User, 2: Transaction}
     */
    private function context(): array
    {
        $category = Category::query()->create([
            'name' => 'Web development',
            'slug' => 'web-development',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->buyer()->create();

        $service = Service::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Landing page',
            'slug' => 'landing-page',
            'description' => 'A landing page.',
            'deliverables' => ['Page'],
            'pricing_type' => PricingType::Fixed,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'revisions' => 1,
            'negotiation_enabled' => true,
            'status' => ServiceStatus::Published,
            'published_at' => now(),
        ]);

        $transaction = Transaction::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service_id' => $service->id,
            'title' => 'Landing page request',
            'description' => 'A landing page request.',
            'currency' => 'USD',
            'budget_minor' => 50000,
            'seller_offer_minor' => 50000,
            'seller_floor_minor' => 45000,
            'delivery_days' => 7,
            'seller_delivery_days' => 7,
            'negotiation_mode' => 'ai',
            'state' => 'draft',
        ]);

        $transaction->offer()->create([
            'seller_id' => $seller->id,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'terms' => 'Listed service terms.',
            'status' => 'active',
        ]);

        return [$buyer, $seller, $transaction];
    }
}
