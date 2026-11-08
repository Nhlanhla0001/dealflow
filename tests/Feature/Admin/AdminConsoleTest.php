<?php

namespace Tests\Feature\Admin;

use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use App\Enums\TransactionState;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_the_admin_console(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->buyer()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/dashboard'));

        $this->actingAs($buyer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_an_admin_can_change_a_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->buyer()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $user), ['role' => 'seller'])
            ->assertRedirect();

        $this->assertSame('seller', $user->fresh()->role->value);
    }

    public function test_an_admin_can_moderate_a_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = $this->service();

        $this->actingAs($admin)
            ->patch(route('admin.services.status', $service), ['status' => 'archived'])
            ->assertRedirect();

        $this->assertSame(ServiceStatus::Archived, $service->fresh()->status);
    }

    public function test_a_party_can_open_a_dispute_and_view_it(): void
    {
        [$order, $buyer] = $this->order();

        $this->actingAs($buyer)
            ->post(route('orders.dispute', $order), [
                'reason' => 'Delivery issue',
                'message' => 'The delivered files were incomplete.',
                'evidence_url' => 'https://example.com/evidence',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('disputed', $order->status);

        $dispute = Dispute::query()->sole();
        $this->assertSame($buyer->id, $dispute->opened_by);
        $this->assertDatabaseHas('dispute_events', [
            'dispute_id' => $dispute->id,
            'type' => 'opened',
        ]);

        $this->actingAs($buyer)
            ->get(route('disputes.show', $dispute))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('disputes/show')
                ->where('dispute.id', $dispute->id));

        $outsider = User::factory()->buyer()->create();
        $this->actingAs($outsider)
            ->get(route('disputes.show', $dispute))
            ->assertForbidden();
    }

    public function test_an_admin_can_resolve_a_dispute(): void
    {
        [$order, $buyer] = $this->order();
        $admin = User::factory()->admin()->create();

        $this->actingAs($buyer)->post(route('orders.dispute', $order), [
            'reason' => 'Scope issue',
            'message' => 'The scope was not delivered.',
        ]);

        $dispute = Dispute::query()->sole();

        $this->actingAs($admin)
            ->get(route('admin.disputes.show', $dispute))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/disputes/show')
                ->where('dispute.id', $dispute->id));

        $this->actingAs($admin)
            ->post(route('admin.disputes.resolve', $dispute), [
                'status' => 'resolved',
                'resolution' => 'The seller will deliver the missing scope.',
            ])
            ->assertRedirect();

        $dispute->refresh();
        $this->assertSame('resolved', $dispute->status);
        $this->assertSame($admin->id, $dispute->resolved_by);
        $this->assertNotNull($dispute->resolved_at);
        $this->assertDatabaseHas('dispute_events', [
            'dispute_id' => $dispute->id,
            'type' => 'resolved',
        ]);
    }

    /**
     * @return array{0: Order, 1: User, 2: User}
     */
    private function order(): array
    {
        $service = $this->service();
        $buyer = User::factory()->buyer()->create();
        $seller = User::factory()->seller()->create();

        $transaction = Transaction::factory()->create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'service_id' => $service->id,
            'state' => TransactionState::Paid,
        ]);

        $order = Order::query()->create([
            'transaction_id' => $transaction->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'amount_minor' => 50000,
            'currency' => 'USD',
            'status' => 'in_progress',
            'paid_at' => now(),
            'started_at' => now(),
        ]);

        return [$order, $buyer, $seller];
    }

    private function service(): Service
    {
        $category = Category::query()->create([
            'name' => 'Design',
            'slug' => 'design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $seller = User::factory()->seller()->create();

        return Service::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Landing page',
            'slug' => 'landing-page-'.uniqid(),
            'description' => 'A landing page.',
            'deliverables' => ['Page'],
            'pricing_type' => PricingType::Fixed,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'revisions' => 2,
            'negotiation_enabled' => true,
            'status' => ServiceStatus::Published,
            'published_at' => now(),
        ]);
    }
}
