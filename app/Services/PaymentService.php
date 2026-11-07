<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\Agreement;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentOrder;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PayPalClient $paypal,
        private readonly AuditService $audit,
    ) {}

    public function createOrder(Transaction $transaction, Agreement $agreement): PaymentOrder
    {
        if ($transaction->state !== TransactionState::Approved) {
            throw new InvalidArgumentException('The transaction must be approved before payment.');
        }

        if ($agreement->status !== AgreementStatus::Approved) {
            throw new InvalidArgumentException('The agreement must be approved by both parties.');
        }

        $existing = $transaction->paymentOrders()
            ->whereIn('status', [PaymentStatus::Created->value, PaymentStatus::Pending->value])
            ->latest()
            ->first();

        if ($existing !== null && $existing->paypal_order_id && $existing->paypal_approval_url) {
            return $existing;
        }

        $paymentOrder = $transaction->paymentOrders()->create([
            'agreement_id' => $agreement->id,
            'status' => PaymentStatus::Pending,
            'amount_minor' => $agreement->price_minor,
            'currency' => $agreement->currency,
        ]);

        $response = $this->paypal->createOrder(
            $paymentOrder,
            URL::temporarySignedRoute('payments.return', now()->addHours(3), [
                'paymentOrder' => $paymentOrder->id,
            ]),
            URL::temporarySignedRoute('payments.cancel', now()->addHours(3), [
                'paymentOrder' => $paymentOrder->id,
            ]),
        );

        $approvalUrl = $this->approvalUrl($response);

        if ($approvalUrl === null) {
            $paymentOrder->update([
                'status' => PaymentStatus::Failed,
                'payload' => $response,
            ]);

            throw new RuntimeException('PayPal did not return an approval link.');
        }

        $paymentOrder->update([
            'paypal_order_id' => $response['id'] ?? null,
            'paypal_approval_url' => $approvalUrl,
            'status' => PaymentStatus::Created,
            'payload' => $response,
        ]);

        if ($transaction->state === TransactionState::Approved) {
            $transaction->transitionTo(TransactionState::PaymentPending);
        }

        $this->audit->record($transaction->buyer, $transaction, 'payment.order_created', [
            'payment_order_id' => $paymentOrder->id,
            'paypal_order_id' => $paymentOrder->paypal_order_id,
        ]);

        return $paymentOrder->fresh();
    }

    public function capture(PaymentOrder $paymentOrder): PaymentOrder
    {
        if ($paymentOrder->status === PaymentStatus::Captured) {
            return $paymentOrder;
        }

        if (! $paymentOrder->paypal_order_id) {
            throw new RuntimeException('The payment order has no PayPal order id.');
        }

        $response = $this->paypal->captureOrder($paymentOrder->paypal_order_id);

        if (($response['status'] ?? null) !== 'COMPLETED') {
            $paymentOrder->update([
                'status' => PaymentStatus::Failed,
                'payload' => $response,
            ]);

            throw new RuntimeException('PayPal did not complete the capture.');
        }

        $captureId = data_get($response, 'purchase_units.0.payments.captures.0.id');

        $paymentOrder->update([
            'status' => PaymentStatus::Captured,
            'capture_id' => $captureId,
            'payload' => $response,
        ]);

        $transaction = $paymentOrder->transaction;

        if ($transaction->state === TransactionState::PaymentPending) {
            $transaction->transitionTo(TransactionState::Paid);
        }

        $this->createOrderRecord($transaction, $paymentOrder);

        $this->audit->record($transaction->buyer, $transaction, 'payment.captured', [
            'payment_order_id' => $paymentOrder->id,
            'capture_id' => $captureId,
        ]);

        return $paymentOrder->fresh();
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhook(array $headers, array $payload): bool
    {
        return $this->paypal->verifyWebhook($headers, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void
    {
        $eventId = (string) ($payload['id'] ?? '');

        if ($eventId === '') {
            throw new InvalidArgumentException('The webhook payload is missing an event id.');
        }

        $event = PaymentEvent::firstOrCreate(
            ['event_id' => $eventId],
            [
                'event_type' => (string) ($payload['event_type'] ?? 'unknown'),
                'payload' => $payload,
            ],
        );

        if (! $event->wasRecentlyCreated && $event->processed_at !== null) {
            return;
        }

        if (($payload['event_type'] ?? null) === 'PAYMENT.CAPTURE.COMPLETED') {
            $orderId = (string) data_get($payload, 'resource.supplementary_data.related_ids.order_id', '');
            $captureId = data_get($payload, 'resource.id');

            $paymentOrder = PaymentOrder::query()
                ->where('paypal_order_id', $orderId)
                ->first();

            if ($paymentOrder !== null && $paymentOrder->status !== PaymentStatus::Captured) {
                $paymentOrder->update([
                    'status' => PaymentStatus::Captured,
                    'capture_id' => $captureId,
                    'payload' => $payload,
                ]);

                $transaction = $paymentOrder->transaction;

                if ($transaction->state === TransactionState::PaymentPending) {
                    $transaction->transitionTo(TransactionState::Paid);
                }

                $this->createOrderRecord($transaction, $paymentOrder);

                $this->audit->record(null, $transaction, 'payment.webhook_captured', [
                    'payment_order_id' => $paymentOrder->id,
                    'capture_id' => $captureId,
                ]);
            }
        }

        $event->update(['processed_at' => now()]);
    }

    private function createOrderRecord(Transaction $transaction, PaymentOrder $paymentOrder): void
    {
        Order::query()->firstOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'buyer_id' => $transaction->buyer_id,
                'seller_id' => $transaction->seller_id,
                'amount_minor' => $paymentOrder->amount_minor,
                'currency' => $paymentOrder->currency,
                'status' => 'paid',
                'paid_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function approvalUrl(array $response): ?string
    {
        /** @var Collection<int, array<string, string>> $links */
        $links = collect($response['links'] ?? []);

        return $links->firstWhere('rel', 'approve')['href']
            ?? $links->firstWhere('rel', 'payer-action')['href']
            ?? null;
    }
}
