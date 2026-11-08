<?php

namespace Tests\Feature\Transactions;

use App\Enums\AgentStatus;
use App\Enums\OfferStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\AgentConfiguration;
use App\Models\PaymentOrder;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_buyer_can_see_the_paypal_approval_url(): void
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::PaymentPending,
        ]);

        PaymentOrder::query()->create([
            'transaction_id' => $transaction->id,
            'status' => PaymentStatus::Created,
            'amount_minor' => 50000,
            'currency' => 'USD',
            'paypal_order_id' => 'ORDER123',
            'paypal_approval_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER123',
            'payload' => ['secret' => 'payer-data'],
        ]);

        $this->actingAs($transaction->buyer)
            ->get(route('transactions.show', $transaction))
            ->assertInertia(function (Assert $page): void {
                $order = $page->toArray()['props']['transaction']['payment_orders'][0];

                $this->assertArrayHasKey('paypal_approval_url', $order);
                $this->assertArrayNotHasKey('payload', $order);
            });

        $this->actingAs($transaction->seller)
            ->get(route('transactions.show', $transaction))
            ->assertInertia(function (Assert $page): void {
                $order = $page->toArray()['props']['transaction']['payment_orders'][0];

                $this->assertArrayNotHasKey('paypal_approval_url', $order);
                $this->assertArrayNotHasKey('payload', $order);
            });
    }

    public function test_the_buyer_cannot_see_the_sellers_private_agent_constraints(): void
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Draft,
        ]);

        $transaction->offer()->create([
            'seller_id' => $transaction->seller_id,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'terms' => 'Terms.',
            'status' => OfferStatus::Active,
        ]);

        AgentConfiguration::query()->create([
            'user_id' => $transaction->buyer_id,
            'transaction_id' => $transaction->id,
            'role' => 'buyer',
            'provider' => 'gemini',
            'model' => 'gemini-3-flash-preview',
            'objective' => 'Buyer objective.',
            'constraints' => ['max_budget_minor' => 50000],
            'max_turns' => 5,
            'spending_limit_minor' => 100,
            'billing_method' => 'prepaid_credits',
            'status' => AgentStatus::Active,
        ]);

        AgentConfiguration::query()->create([
            'user_id' => $transaction->seller_id,
            'transaction_id' => $transaction->id,
            'role' => 'seller',
            'provider' => 'gemini',
            'model' => 'gemini-3-flash-preview',
            'objective' => 'Seller objective.',
            'constraints' => ['floor_minor' => 45000],
            'max_turns' => 5,
            'spending_limit_minor' => 100,
            'billing_method' => 'prepaid_credits',
            'status' => AgentStatus::Active,
        ]);

        $this->actingAs($transaction->buyer)
            ->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertInertia(function (Assert $page): void {
                $configurations = collect(
                    $page->toArray()['props']['transaction']['agent_configurations'],
                );

                $buyer = $configurations->firstWhere('role', 'buyer');
                $seller = $configurations->firstWhere('role', 'seller');

                $this->assertArrayHasKey('constraints', $buyer);
                $this->assertArrayNotHasKey('constraints', $seller);
                $this->assertArrayNotHasKey('objective', $seller);
                $this->assertArrayNotHasKey('spending_limit_minor', $seller);
                $this->assertSame('gemini', $seller['provider']);
            });
    }
}
