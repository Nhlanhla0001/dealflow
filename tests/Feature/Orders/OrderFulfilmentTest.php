<?php

namespace Tests\Feature\Orders;

use App\Enums\PricingType;
use App\Enums\ServiceStatus;
use App\Enums\TransactionState;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderFulfilmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seller_can_start_progress_and_deliver_an_order(): void
    {
        [$order, $seller] = $this->order();

        $this->actingAs($seller)
            ->post(route('orders.start', $order))
            ->assertRedirect();

        $this->assertSame('in_progress', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->started_at);

        $this->actingAs($seller)
            ->post(route('orders.progress', $order), [
                'progress_message' => 'First draft is underway.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'type' => 'progress',
        ]);

        $this->actingAs($seller)
            ->post(route('orders.deliver', $order), [
                'delivery_message' => 'The deliverables are ready.',
                'delivery_url' => 'https://example.com/delivery',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame(
            'https://example.com/delivery',
            $order->delivery_url,
        );
        $this->assertNotNull($order->delivered_at);
    }

    public function test_the_buyer_can_request_a_revision_and_then_accept_delivery(): void
    {
        [$order, $seller, $buyer] = $this->order();

        $this->actingAs($seller)
            ->post(route('orders.start', $order));
        $this->actingAs($seller)
            ->post(route('orders.deliver', $order), [
                'delivery_message' => 'First delivery.',
            ]);

        $this->actingAs($buyer)
            ->post(route('orders.revision', $order), [
                'revision_message' => 'Please adjust the hero section.',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('revision_requested', $order->status);
        $this->assertSame(1, $order->revisions_used);

        $this->actingAs($seller)
            ->post(route('orders.deliver', $order), [
                'delivery_message' => 'Revision complete.',
            ]);

        $this->actingAs($buyer)
            ->post(route('orders.accept', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);
    }

    public function test_revision_limit_is_enforced(): void
    {
        [$order, $seller, $buyer] = $this->order();

        $this->actingAs($seller)->post(route('orders.start', $order));
        $this->actingAs($seller)->post(route('orders.deliver', $order), [
            'delivery_message' => 'Delivery.',
        ]);

        $order->update(['revisions_used' => 2]);

        $this->actingAs($buyer)
            ->post(route('orders.revision', $order), [
                'revision_message' => 'One more change.',
            ])
            ->assertRedirect();

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame(2, $order->fresh()->revisions_used);
    }

    public function test_only_the_right_party_can_perform_each_action(): void
    {
        [$order, $seller, $buyer] = $this->order();
        $outsider = User::factory()->buyer()->create();

        $this->actingAs($buyer)
            ->post(route('orders.start', $order))
            ->assertForbidden();

        $this->actingAs($seller)
            ->post(route('orders.accept', $order))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_a_party_can_open_a_dispute_with_an_event(): void
    {
        [$order, $seller] = $this->order();

        $this->actingAs($seller)
            ->post(route('orders.dispute', $order), [
                'reason' => 'Scope disagreement',
                'message' => 'The buyer requested work outside the agreement.',
            ])
            ->assertRedirect();

        $this->assertSame('disputed', $order->fresh()->status);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'type' => 'disputed',
        ]);
    }

    public function test_order_pages_show_the_timeline(): void
    {
        [$order] = $this->order();

        OrderEvent::create([
            'order_id' => $order->id,
            'user_id' => $order->seller_id,
            'type' => 'started',
            'message' => 'Work started.',
        ]);

        $this->actingAs($order->buyer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('orders/index')
                ->has('orders', 1));

        $this->actingAs($order->buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('orders/show')
                ->where('order.id', $order->id)
                ->has('order.events', 1)
                ->where('isBuyer', true)
                ->where('isSeller', false));
    }

    /**
     * @return array{0: Order, 1: User, 2: User}
     */
    private function order(): array
    {
        $category = Category::query()->create([
            'name' => 'Design',
            'slug' => 'design',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->buyer()->create();

        $service = Service::query()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Landing page design',
            'slug' => 'landing-page-design',
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
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return [$order, $seller, $buyer];
    }
}
