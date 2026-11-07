<?php

namespace Tests\Feature\Payments;

use App\Enums\AgreementStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\Agreement;
use App\Models\Order;
use App\Models\PaymentOrder;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_buyer_sees_the_checkout_wizard_for_an_approved_agreement(): void
    {
        [$transaction] = $this->approvedTransaction();

        $this->actingAs($transaction->buyer)
            ->get(route('checkout.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('checkout/show')
                ->where('canPay', true)
                ->where('currentStep', 'paypal')
                ->has('steps', 7)
                ->where('transaction.id', $transaction->id));
    }

    public function test_the_seller_can_view_the_checkout_but_cannot_pay(): void
    {
        [$transaction] = $this->approvedTransaction();

        $this->actingAs($transaction->seller)
            ->get(route('checkout.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('checkout/show')
                ->where('canPay', false));
    }

    public function test_a_captured_payment_shows_the_order_step(): void
    {
        [$transaction, $agreement] = $this->approvedTransaction();

        PaymentOrder::query()->create([
            'transaction_id' => $transaction->id,
            'agreement_id' => $agreement->id,
            'status' => PaymentStatus::Captured,
            'amount_minor' => 50000,
            'currency' => 'USD',
            'paypal_order_id' => 'ORDER123',
            'capture_id' => 'CAP123',
        ]);

        Order::query()->create([
            'transaction_id' => $transaction->id,
            'buyer_id' => $transaction->buyer_id,
            'seller_id' => $transaction->seller_id,
            'amount_minor' => 50000,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($transaction->buyer)
            ->get(route('checkout.show', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('checkout/show')
                ->where('currentStep', 'order')
                ->where('order.status', 'paid')
                ->where('payment.capture_id', 'CAP123'));
    }

    public function test_an_unrelated_user_cannot_view_the_checkout(): void
    {
        [$transaction] = $this->approvedTransaction();
        $outsider = User::factory()->buyer()->create();

        $this->actingAs($outsider)
            ->get(route('checkout.show', $transaction))
            ->assertForbidden();
    }

    /**
     * @return array{0: Transaction, 1: Agreement}
     */
    private function approvedTransaction(): array
    {
        $transaction = Transaction::factory()->create([
            'state' => TransactionState::Approved,
        ]);

        $agreement = $transaction->agreements()->create([
            'version' => 1,
            'price_minor' => 50000,
            'currency' => 'USD',
            'delivery_days' => 7,
            'terms' => ['scope' => $transaction->title],
            'status' => AgreementStatus::Approved,
            'hash' => hash('sha256', 'checkout-agreement'),
        ]);

        return [$transaction, $agreement];
    }
}
