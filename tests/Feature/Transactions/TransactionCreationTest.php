<?php

namespace Tests\Feature\Transactions;

use App\Enums\TransactionState;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_create_a_transaction(): void
    {
        $buyer = User::factory()->buyer()->create();
        $seller = User::factory()->seller()->create();

        $response = $this->actingAs($buyer)->post(route('transactions.store'), [
            'title' => 'Business website build',
            'description' => 'A seven-page marketing site with a contact form and CMS.',
            'seller_id' => $seller->id,
            'budget' => '500.00',
            'delivery_days' => 7,
        ]);

        $transaction = Transaction::query()->sole();

        $response->assertRedirect(route('transactions.show', $transaction));
        $this->assertSame($buyer->id, $transaction->buyer_id);
        $this->assertSame($seller->id, $transaction->seller_id);
        $this->assertSame(50000, $transaction->budget_minor);
        $this->assertSame(TransactionState::Draft, $transaction->state);
        $this->assertDatabaseHas('audit_logs', [
            'transaction_id' => $transaction->id,
            'user_id' => $buyer->id,
            'action' => 'transaction.created',
        ]);
    }

    public function test_seller_cannot_create_a_transaction(): void
    {
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();

        $response = $this->actingAs($seller)->post(route('transactions.store'), [
            'title' => 'Business website build',
            'description' => 'A seven-page marketing site.',
            'seller_id' => $otherSeller->id,
            'budget' => '500.00',
            'delivery_days' => 7,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_the_selected_seller_must_have_the_seller_role(): void
    {
        $buyer = User::factory()->buyer()->create();
        $notSeller = User::factory()->buyer()->create();

        $response = $this->actingAs($buyer)->post(route('transactions.store'), [
            'title' => 'Business website build',
            'description' => 'A seven-page marketing site.',
            'seller_id' => $notSeller->id,
            'budget' => '500.00',
            'delivery_days' => 7,
        ]);

        $response->assertSessionHasErrors('seller_id');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_only_the_parties_can_view_a_transaction(): void
    {
        $transaction = Transaction::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($transaction->buyer)
            ->get(route('transactions.show', $transaction))
            ->assertOk();

        $this->actingAs($transaction->seller)
            ->get(route('transactions.show', $transaction))
            ->assertOk();

        $this->actingAs($outsider)
            ->get(route('transactions.show', $transaction))
            ->assertForbidden();
    }

    public function test_sellers_cannot_open_the_create_page(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->get(route('transactions.create'))
            ->assertForbidden();
    }

    public function test_buyers_can_open_the_create_page(): void
    {
        $buyer = User::factory()->buyer()->create();

        $this->actingAs($buyer)
            ->get(route('transactions.create'))
            ->assertOk();
    }
}
