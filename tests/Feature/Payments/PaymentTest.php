<?php

namespace Tests\Feature\Payments;

use App\Enums\AgreementStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\Agreement;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'services.paypal.client_id' => 'test-client',
            'services.paypal.secret' => 'test-secret',
            'services.paypal.base_url' => 'https://api-m.sandbox.paypal.com',
            'services.paypal.webhook_id' => 'WH-TEST',
        ]);
    }

    public function test_buyer_can_create_a_paypal_checkout(): void
    {
        $this->fakePayPal();
        [$transaction] = $this->approvedTransaction();

        $response = $this->actingAs($transaction->buyer)
            ->post(route('transactions.checkout', $transaction));

        $response->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=ORDER123');

        $paymentOrder = $transaction->paymentOrders()->sole();

        $this->assertSame(PaymentStatus::Created, $paymentOrder->status);
        $this->assertSame('ORDER123', $paymentOrder->paypal_order_id);
        $this->assertSame(
            'https://www.sandbox.paypal.com/checkoutnow?token=ORDER123',
            $paymentOrder->paypal_approval_url,
        );
        $this->assertSame(TransactionState::PaymentPending, $transaction->fresh()->state);
    }

    public function test_seller_cannot_create_a_paypal_checkout(): void
    {
        $this->fakePayPal();
        [$transaction] = $this->approvedTransaction();

        $this->actingAs($transaction->seller)
            ->post(route('transactions.checkout', $transaction))
            ->assertForbidden();
    }

    public function test_paypal_return_captures_and_marks_the_transaction_paid(): void
    {
        $this->fakePayPal();
        [$transaction] = $this->approvedTransaction();

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.checkout', $transaction));

        $paymentOrder = $transaction->paymentOrders()->sole();

        $returnUrl = URL::temporarySignedRoute('payments.return', now()->addHour(), [
            'paymentOrder' => $paymentOrder->id,
        ]);

        $this->actingAs($transaction->buyer)
            ->get($returnUrl)
            ->assertRedirect(route('checkout.show', $transaction));

        $paymentOrder->refresh();

        $this->assertSame(PaymentStatus::Captured, $paymentOrder->status);
        $this->assertSame('CAP123', $paymentOrder->capture_id);
        $this->assertSame(TransactionState::Paid, $transaction->fresh()->state);
        $this->assertDatabaseHas('orders', [
            'transaction_id' => $transaction->id,
            'status' => 'paid',
            'amount_minor' => 50000,
        ]);
    }

    public function test_cancelling_paypal_checkout_cancels_the_transaction(): void
    {
        $this->fakePayPal();
        [$transaction] = $this->approvedTransaction();

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.checkout', $transaction));

        $paymentOrder = $transaction->paymentOrders()->sole();

        $cancelUrl = URL::temporarySignedRoute('payments.cancel', now()->addHour(), [
            'paymentOrder' => $paymentOrder->id,
        ]);

        $this->actingAs($transaction->buyer)
            ->get($cancelUrl)
            ->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame(PaymentStatus::Cancelled, $paymentOrder->fresh()->status);
        $this->assertSame(TransactionState::Cancelled, $transaction->fresh()->state);
    }

    public function test_a_verified_webhook_marks_the_payment_paid_and_is_idempotent(): void
    {
        $this->fakePayPal();
        [$transaction] = $this->approvedTransaction();

        $this->actingAs($transaction->buyer)
            ->post(route('transactions.checkout', $transaction));

        $paymentOrder = $transaction->paymentOrders()->sole();

        $payload = [
            'id' => 'WH-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP123',
                'supplementary_data' => [
                    'related_ids' => ['order_id' => 'ORDER123'],
                ],
            ],
        ];

        $this->postJson(route('webhooks.paypal'), $payload, $this->webhookHeaders())
            ->assertOk();

        $this->assertSame(PaymentStatus::Captured, $paymentOrder->fresh()->status);
        $this->assertSame(TransactionState::Paid, $transaction->fresh()->state);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseHas('orders', [
            'transaction_id' => $transaction->id,
            'status' => 'paid',
        ]);

        $this->postJson(route('webhooks.paypal'), $payload, $this->webhookHeaders())
            ->assertOk();

        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_an_invalid_webhook_signature_is_rejected(): void
    {
        $this->fakePayPal('FAILURE');

        $this->postJson(route('webhooks.paypal'), [
            'id' => 'WH-2',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ], $this->webhookHeaders())->assertStatus(400);

        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_a_paypal_verification_error_is_rejected_without_a_server_error(): void
    {
        $this->fakePayPal('ERROR');

        $this->postJson(route('webhooks.paypal'), [
            'id' => 'WH-3',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ], $this->webhookHeaders())->assertStatus(400);

        $this->assertDatabaseCount('payment_events', 0);
    }

    private function fakePayPal(string $verification = 'SUCCESS'): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'fake-token',
                'expires_in' => 32400,
            ], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'ORDER123',
                'status' => 'CREATED',
                'links' => [
                    [
                        'rel' => 'approve',
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER123',
                    ],
                ],
            ], 201),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER123/capture' => Http::response([
                'id' => 'ORDER123',
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'payments' => [
                            'captures' => [
                                ['id' => 'CAP123', 'status' => 'COMPLETED'],
                            ],
                        ],
                    ],
                ],
            ], 201),
            'api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => $verification === 'ERROR'
                ? Http::response(['name' => 'INVALID_REQUEST'], 400)
                : Http::response(['verification_status' => $verification], 200),
        ]);
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
            'hash' => hash('sha256', 'test-agreement'),
        ]);

        return [$transaction, $agreement];
    }

    /**
     * @return array<string, string>
     */
    private function webhookHeaders(): array
    {
        return [
            'paypal-auth-algo' => 'SHA256withRSA',
            'paypal-cert-url' => 'https://api.paypal.com/cert',
            'paypal-transmission-id' => 'transmission-id',
            'paypal-transmission-sig' => 'signature',
            'paypal-transmission-time' => '2026-11-07T00:00:00Z',
        ];
    }
}
