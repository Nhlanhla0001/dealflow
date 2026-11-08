<?php

namespace Tests\Feature\Wizards;

use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WizardDraft;
use App\Notifications\BuyerRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerRequestWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_buyer_can_start_a_request_from_a_published_service(): void
    {
        [$buyer, $seller, $service] = $this->context();

        $this->actingAs($buyer)
            ->get(route('buyer.requests.create', ['service' => $service->slug]))
            ->assertRedirect();

        $draft = WizardDraft::query()->sole();

        $this->assertSame('buyer_request', $draft->wizard_type);
        $this->assertSame('outcome', $draft->current_step);
        $this->assertSame(['service'], $draft->completed_steps);
        $this->assertSame($service->id, $draft->data['service_id']);
        $this->assertEquals($service->price_minor / 100, $draft->data['budget']);
        $this->assertSame($seller->id, $service->seller_id);
    }

    public function test_an_unpublished_service_cannot_be_requested(): void
    {
        [$buyer, , $service] = $this->context(ServiceStatus::Draft);

        $this->actingAs($buyer)
            ->get(route('buyer.requests.create', ['service' => $service->slug]))
            ->assertNotFound();
    }

    public function test_a_seller_cannot_start_a_buyer_request(): void
    {
        [, $seller, $service] = $this->context();

        $this->actingAs($seller)
            ->get(route('buyer.requests.create', ['service' => $service->slug]))
            ->assertForbidden();
    }

    public function test_a_complete_request_creates_a_transaction_and_offer(): void
    {
        [$buyer, $seller, $service] = $this->context();

        $this->actingAs($buyer)
            ->get(route('buyer.requests.create', ['service' => $service->slug]));

        $draft = WizardDraft::query()->sole();

        $steps = [
            'outcome' => [
                'title' => 'Product launch landing page',
                'description' => 'A launch page with waitlist signups.',
                'required_deliverables' => ['Responsive page', 'Waitlist form'],
            ],
            'budget' => [
                'budget' => 900,
                'currency' => 'USD',
                'delivery_days' => 8,
            ],
            'conditions' => [
                'essential_requirements' => 'Accessible forms.',
                'negotiable_conditions' => 'Delivery can move by two days.',
            ],
            'negotiation' => [
                'negotiation_mode' => 'ai',
            ],
        ];

        foreach ($steps as $step => $data) {
            $this->actingAs($buyer)
                ->postJson(route('wizard-drafts.step', $draft), [
                    'step' => $step,
                    'data' => $data,
                    'version' => $draft->fresh()->version,
                ])
                ->assertOk();

            $draft->refresh();
        }

        $this->actingAs($buyer)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'review',
                'data' => [],
                'version' => $draft->version,
            ])
            ->assertOk();

        $draft->refresh();
        $this->assertSame('publish', $draft->current_step);

        $this->actingAs($buyer)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->version,
            ])
            ->assertCreated()
            ->assertJsonPath('type', 'transaction')
            ->assertJsonPath('redirect', route('transactions.show', Transaction::query()->sole()));

        $transaction = Transaction::query()->sole();

        $this->assertSame($buyer->id, $transaction->buyer_id);
        $this->assertSame($seller->id, $transaction->seller_id);
        $this->assertSame($service->id, $transaction->service_id);
        $this->assertSame('ai', $transaction->negotiation_mode);
        $this->assertSame(90000, $transaction->budget_minor);
        $this->assertSame(8, $transaction->delivery_days);
        $this->assertSame($service->price_minor, $transaction->offer->price_minor);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $seller->id,
            'type' => BuyerRequestReceived::class,
        ]);
    }

    public function test_an_incomplete_request_cannot_be_published(): void
    {
        [$buyer, , $service] = $this->context();

        $this->actingAs($buyer)
            ->get(route('buyer.requests.create', ['service' => $service->slug]));

        $draft = WizardDraft::query()->sole();

        $this->actingAs($buyer)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->version,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_another_buyer_cannot_edit_the_draft(): void
    {
        [$buyer, , $service] = $this->context();
        $other = User::factory()->buyer()->create();

        $this->actingAs($buyer)
            ->get(route('buyer.requests.create', ['service' => $service->slug]));

        $draft = WizardDraft::query()->sole();

        $this->actingAs($other)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'outcome',
                'data' => [
                    'title' => 'Hijack',
                    'description' => 'Hijack',
                    'required_deliverables' => ['Hijack'],
                ],
                'version' => $draft->version,
            ])
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: Service}
     */
    private function context(ServiceStatus $status = ServiceStatus::Published): array
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
            'title' => 'Landing page design and build',
            'slug' => 'landing-page-design-and-build',
            'description' => 'A focused landing page.',
            'deliverables' => ['Responsive page', 'Copy structure'],
            'pricing_type' => PricingType::Fixed,
            'price_minor' => 85000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'revisions' => 2,
            'negotiation_enabled' => true,
            'status' => $status,
            'published_at' => $status === ServiceStatus::Published ? now() : null,
        ]);

        return [$buyer, $seller, $service];
    }
}
