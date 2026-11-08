<?php

namespace App\Http\Controllers;

use App\Enums\AgreementStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\PaymentOrder;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        Transaction $transaction,
        PaymentService $payments,
    ): RedirectResponse {
        $this->authorize('create', [PaymentOrder::class, $transaction]);

        $agreement = $transaction->latestAgreement;

        if ($agreement === null || $agreement->status !== AgreementStatus::Approved) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('The agreement must be approved before payment.'),
            ]);

            return back();
        }

        try {
            $paymentOrder = $payments->createOrder($transaction, $agreement);
        } catch (Throwable $exception) {
            report($exception);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('PayPal could not create the checkout. Please try again.'),
            ]);

            return back();
        }

        return redirect()->away($paymentOrder->paypal_approval_url);
    }

    public function return(
        Request $request,
        PaymentOrder $paymentOrder,
        PaymentService $payments,
    ): RedirectResponse {
        try {
            $payments->capture($paymentOrder);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Payment captured and verified by PayPal.'),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('PayPal could not confirm the payment.'),
            ]);
        }

        return to_route('checkout.show', $paymentOrder->transaction_id);
    }

    public function cancel(Request $request, PaymentOrder $paymentOrder): RedirectResponse
    {
        if ($paymentOrder->status === PaymentStatus::Captured) {
            return to_route('transactions.show', $paymentOrder->transaction_id);
        }

        $paymentOrder->update(['status' => PaymentStatus::Cancelled]);

        $transaction = $paymentOrder->transaction;

        if ($transaction->state === TransactionState::PaymentPending) {
            $transaction->transitionTo(TransactionState::Cancelled);
        }

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => __('PayPal checkout was cancelled.'),
        ]);

        return to_route('transactions.show', $paymentOrder->transaction_id);
    }
}
