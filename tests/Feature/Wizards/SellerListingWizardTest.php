<?php

namespace Tests\Feature\Wizards;

use App\Enums\ServiceStatus;
use App\Enums\WizardStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use App\Models\WizardDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SellerListingWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seller_can_start_a_listing_draft(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->get(route('seller.services.create'))
            ->assertRedirect();

        $draft = WizardDraft::query()->sole();

        $this->assertSame($seller->id, $draft->user_id);
        $this->assertSame('seller_listing', $draft->wizard_type);
        $this->assertSame('category', $draft->current_step);
        $this->assertSame(1, $draft->version);
        $this->assertSame(WizardStatus::Draft, $draft->status);
    }

    public function test_a_buyer_cannot_start_a_listing_draft(): void
    {
        $buyer = User::factory()->buyer()->create();

        $this->actingAs($buyer)
            ->get(route('seller.services.create'))
            ->assertForbidden();

        $this->assertDatabaseCount('wizard_drafts', 0);
    }

    public function test_a_step_is_only_complete_after_its_rules_pass(): void
    {
        $seller = User::factory()->seller()->create();
        $draft = $this->startDraft($seller);

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'category',
                'data' => [],
                'version' => $draft->version,
            ])
            ->assertUnprocessable();

        $draft->refresh();
        $this->assertSame([], $draft->completed_steps);
        $this->assertSame('category', $draft->current_step);
        $this->assertSame(1, $draft->version);
    }

    public function test_a_valid_step_advances_and_persists(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $draft = $this->startDraft($seller);

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'category',
                'data' => ['category_id' => $category->id],
                'version' => $draft->version,
            ])
            ->assertOk()
            ->assertJsonPath('draft.current_step', 'basics')
            ->assertJsonPath('draft.completed_steps.0', 'category');

        $draft->refresh();

        $this->assertSame($category->id, $draft->data['category_id']);
        $this->assertSame(2, $draft->version);

        $this->actingAs($seller)
            ->get(route('seller.services.wizard', $draft))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('seller/services/wizard')
                ->where('draft.current_step', 'basics')
                ->where('draft.data.category_id', $category->id));
    }

    public function test_saving_a_draft_does_not_complete_an_invalid_step(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $draft = $this->startDraft($seller);

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'category',
                'data' => ['category_id' => $category->id],
                'version' => $draft->version,
            ])
            ->assertOk();

        $draft->refresh();

        $this->actingAs($seller)
            ->patchJson(route('wizard-drafts.update', $draft), [
                'data' => ['title' => 'A partial title'],
                'current_step' => 'basics',
                'version' => $draft->version,
            ])
            ->assertOk()
            ->assertJsonPath('draft.data.title', 'A partial title')
            ->assertJsonPath('draft.completed_steps', ['category'])
            ->assertJsonPath('draft.current_step', 'basics');

        $this->assertSame(3, $draft->fresh()->version);
    }

    public function test_a_future_step_cannot_be_skipped(): void
    {
        $seller = User::factory()->seller()->create();
        $draft = $this->startDraft($seller);

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'pricing',
                'data' => [
                    'pricing_type' => 'fixed',
                    'price' => 500,
                    'currency' => 'USD',
                ],
                'version' => $draft->version,
            ])
            ->assertUnprocessable();

        $this->assertSame([], $draft->fresh()->completed_steps);
    }

    public function test_a_stale_version_returns_a_conflict_with_the_latest_draft(): void
    {
        $seller = User::factory()->seller()->create();
        $draft = $this->startDraft($seller);

        $this->actingAs($seller)
            ->patchJson(route('wizard-drafts.update', $draft), [
                'data' => ['title' => 'First tab'],
                'current_step' => 'basics',
                'version' => 1,
            ])
            ->assertOk();

        $this->actingAs($seller)
            ->patchJson(route('wizard-drafts.update', $draft), [
                'data' => ['title' => 'Second tab'],
                'current_step' => 'basics',
                'version' => 1,
            ])
            ->assertStatus(409)
            ->assertJsonPath('draft.version', 2);
    }

    public function test_another_user_cannot_read_or_write_the_draft(): void
    {
        $seller = User::factory()->seller()->create();
        $other = User::factory()->seller()->create();
        $draft = $this->startDraft($seller);

        $this->actingAs($other)
            ->get(route('seller.services.wizard', $draft))
            ->assertForbidden();

        $this->actingAs($other)
            ->patchJson(route('wizard-drafts.update', $draft), [
                'data' => [],
                'current_step' => 'category',
                'version' => 1,
            ])
            ->assertForbidden();
    }

    public function test_an_expired_draft_returns_gone(): void
    {
        $seller = User::factory()->seller()->create();
        $draft = $this->startDraft($seller);
        $draft->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($seller)
            ->patchJson(route('wizard-drafts.update', $draft), [
                'data' => [],
                'current_step' => 'category',
                'version' => 1,
            ])
            ->assertStatus(410);
    }

    public function test_changing_an_earlier_step_invalidates_later_progress(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $otherCategory = $this->category('design');
        $draft = $this->startDraft($seller);

        $this->completeRequiredSteps($seller, $draft, $category);
        $draft->refresh();
        $this->assertContains('negotiation', $draft->completed_steps);
        $this->assertSame('publish', $draft->current_step);

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'category',
                'data' => ['category_id' => $otherCategory->id],
                'version' => $draft->version,
            ])
            ->assertOk()
            ->assertJsonPath('draft.current_step', 'basics');

        $draft->refresh();
        $this->assertSame(['category'], $draft->completed_steps);
    }

    public function test_a_complete_wizard_publishes_a_service(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $draft = $this->startDraft($seller);

        $this->completeRequiredSteps($seller, $draft, $category);
        $draft->refresh();

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->version,
            ])
            ->assertCreated()
            ->assertJsonPath('service.slug', 'a-complete-service-listing');

        $service = Service::query()->sole();
        $this->assertSame($seller->id, $service->seller_id);
        $this->assertSame(ServiceStatus::Published, $service->status);
        $this->assertSame(150000, $service->price_minor);
        $this->assertNotNull($service->published_at);
        $this->assertSame(WizardStatus::Completed, $draft->fresh()->status);
    }

    public function test_an_incomplete_wizard_cannot_publish(): void
    {
        $seller = User::factory()->seller()->create();
        $draft = $this->startDraft($seller);

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->version,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseCount('services', 0);
    }

    public function test_a_seller_can_edit_and_republish_a_service(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $service = Service::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Original listing',
            'slug' => 'original-listing',
            'description' => 'Original description',
            'deliverables' => ['Original deliverable'],
            'pricing_type' => 'fixed',
            'price_minor' => 100000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'revisions' => 1,
            'negotiation_enabled' => true,
            'status' => ServiceStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($seller)
            ->get(route('seller.services.edit', $service))
            ->assertRedirect();

        $draft = WizardDraft::query()->sole();
        $this->assertSame('preview', $draft->current_step);
        $this->assertContains('negotiation', $draft->completed_steps);

        $this->actingAs($seller)
            ->patchJson(route('wizard-drafts.update', $draft), [
                'data' => ['title' => 'Updated listing'],
                'current_step' => 'preview',
                'version' => $draft->version,
            ])
            ->assertOk();

        $draft->refresh();

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.publish', $draft), [
                'confirm' => true,
                'version' => $draft->version,
            ])
            ->assertCreated();

        $this->assertSame('Updated listing', $service->fresh()->title);
        $this->assertSame(1, Service::query()->count());
    }

    private function startDraft(User $seller): WizardDraft
    {
        $this->actingAs($seller)
            ->get(route('seller.services.create'))
            ->assertRedirect();

        return WizardDraft::query()->latest('id')->firstOrFail();
    }

    private function category(string $slug = 'web-development'): Category
    {
        return Category::query()->create([
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    private function completeRequiredSteps(User $seller, WizardDraft $draft, Category $category): void
    {
        $steps = [
            'category' => ['category_id' => $category->id],
            'basics' => [
                'title' => 'A complete service listing',
                'description' => 'A complete description for the listing.',
                'deliverables' => ['A deliverable'],
            ],
            'pricing' => [
                'pricing_type' => 'fixed',
                'price' => 1500,
                'currency' => 'USD',
                'includes' => 'Everything needed.',
            ],
            'delivery' => [
                'delivery_days' => 10,
                'revisions' => 2,
                'exclusions' => null,
                'requirements' => 'A brief.',
            ],
            'media' => [
                'image_urls' => [],
                'supporting_info' => 'Supporting information.',
            ],
            'negotiation' => [
                'negotiation_enabled' => true,
            ],
        ];

        foreach ($steps as $step => $data) {
            $this->actingAs($seller)
                ->postJson(route('wizard-drafts.step', $draft), [
                    'step' => $step,
                    'data' => $data,
                    'version' => $draft->fresh()->version,
                ])
                ->assertOk();

            $draft->refresh();
        }

        $this->actingAs($seller)
            ->postJson(route('wizard-drafts.step', $draft), [
                'step' => 'preview',
                'data' => [],
                'version' => $draft->version,
            ])
            ->assertOk();
    }
}
