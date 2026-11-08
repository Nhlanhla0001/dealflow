<?php

namespace Tests\Feature\Marketplace;

use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_lists_categories_and_published_services(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $this->service($seller, $category, 'Published service', 'published-service', ServiceStatus::Published);
        $this->service($seller, $category, 'Draft service', 'draft-service', ServiceStatus::Draft);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('welcome')
                ->has('categories', 1)
                ->has('featuredServices', 1)
                ->where('featuredServices.0.title', 'Published service'));
    }

    public function test_the_services_index_only_lists_published_services(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $this->service($seller, $category, 'Published one', 'published-one', ServiceStatus::Published);
        $this->service($seller, $category, 'Published two', 'published-two', ServiceStatus::Published);
        $this->service($seller, $category, 'Draft one', 'draft-one', ServiceStatus::Draft);
        $this->service($seller, $category, 'Archived one', 'archived-one', ServiceStatus::Archived);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/index')
                ->has('services.data', 2)
                ->has('categories', 1));
    }

    public function test_services_can_be_searched_filtered_and_sorted(): void
    {
        $web = $this->category('web-development');
        $design = $this->category('design');
        $seller = User::factory()->seller()->create();
        $this->service($seller, $web, 'Laravel API build', 'laravel-api-build', ServiceStatus::Published, 120000);
        $this->service($seller, $design, 'Landing page design', 'landing-page-design', ServiceStatus::Published, 80000);

        $this->get(route('services.index', ['q' => 'Laravel']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('services.data', 1)
                ->where('services.data.0.title', 'Laravel API build'));

        $this->get(route('services.index', ['category' => 'design']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('services.data', 1)
                ->where('services.data.0.title', 'Landing page design'));

        $this->get(route('services.index', ['sort' => 'price_asc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('services.data.0.price_minor', 80000));

        $this->get(route('services.index', ['price_min' => '1000']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('services.data', 1)
                ->where('services.data.0.title', 'Laravel API build'));
    }

    public function test_a_published_service_has_a_public_page(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $service = $this->service($seller, $category, 'Public service', 'public-service', ServiceStatus::Published);

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/show')
                ->where('service.slug', 'public-service')
                ->where('service.seller.id', $seller->id));
    }

    public function test_unpublished_services_are_not_public(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $service = $this->service($seller, $category, 'Draft service', 'draft-service', ServiceStatus::Draft);

        $this->get(route('services.show', $service))->assertNotFound();
    }

    public function test_category_and_seller_pages_are_public(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $this->service($seller, $category, 'Public service', 'public-service', ServiceStatus::Published);

        $this->get(route('categories.show', $category))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/index')
                ->where('selectedCategory.slug', 'web-development'));

        $this->get(route('sellers.show', $seller))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketplace/seller')
                ->has('services', 1));

        $buyer = User::factory()->buyer()->create();
        $this->get(route('sellers.show', $buyer))->assertNotFound();
    }

    public function test_the_sitemap_contains_public_urls(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $service = $this->service($seller, $category, 'Public service', 'public-service', ServiceStatus::Published);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('services.show', $service), false)
            ->assertSee(route('categories.show', $category), false);
    }

    public function test_a_buyer_can_start_a_transaction_from_a_service(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->buyer()->create();
        $service = $this->service($seller, $category, 'Public service', 'public-service', ServiceStatus::Published, 50000);

        $this->actingAs($buyer)
            ->get(route('transactions.create', ['service' => $service->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('transactions/create')
                ->where('prefill.service_id', $service->id)
                ->where('prefill.seller_id', $seller->id)
                ->where('prefill.title', 'Public service'));

        $this->actingAs($buyer)
            ->post(route('transactions.store'), [
                'service_id' => $service->id,
                'seller_id' => $seller->id,
                'title' => 'Public service',
                'description' => 'A project based on the public service.',
                'budget' => 500,
                'delivery_days' => 7,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_a_service_cannot_be_attached_to_the_wrong_seller(): void
    {
        $category = $this->category();
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();
        $buyer = User::factory()->buyer()->create();
        $service = $this->service($seller, $category, 'Public service', 'public-service', ServiceStatus::Published);

        $this->actingAs($buyer)
            ->post(route('transactions.store'), [
                'service_id' => $service->id,
                'seller_id' => $otherSeller->id,
                'title' => 'Public service',
                'description' => 'A project based on the public service.',
                'budget' => 500,
                'delivery_days' => 7,
            ])
            ->assertSessionHasErrors('service_id');

        $this->assertDatabaseCount('transactions', 0);
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

    private function service(
        User $seller,
        Category $category,
        string $title,
        string $slug,
        ServiceStatus $status,
        int $priceMinor = 100000,
    ): Service {
        return Service::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => $title,
            'slug' => $slug,
            'description' => 'A clear description for '.$title.'.',
            'deliverables' => ['A deliverable'],
            'pricing_type' => PricingType::Fixed,
            'price_minor' => $priceMinor,
            'currency' => 'USD',
            'delivery_days' => 7,
            'revisions' => 1,
            'negotiation_enabled' => true,
            'status' => $status,
            'published_at' => $status === ServiceStatus::Published ? now() : null,
        ]);
    }
}
