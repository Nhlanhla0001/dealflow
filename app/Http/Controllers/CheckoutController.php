<?php

namespace App\Http\Controllers;

use App\Enums\AgreementStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(Request $request, Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        $transaction->load([
            'buyer:id,name',
            'seller:id,name',
            'latestAgreement.approvals',
            'paymentOrders',
            'order',
        ]);

        $agreement = $transaction->latestAgreement;
        $payment = $transaction->paymentOrders->sortByDesc('id')->first();
        $order = $transaction->order;

        $canPay = $transaction->buyer_id === $request->user()->id
            && $transaction->state === TransactionState::Approved
            && $agreement?->status === AgreementStatus::Approved;

        $steps = [
            ['key' => 'agreement', 'label' => 'Agreement', 'description' => 'The approved terms.'],
            ['key' => 'amount', 'label' => 'Amount', 'description' => 'Price, currency, and fees.'],
            ['key' => 'method', 'label' => 'Payment method', 'description' => 'How the payment is handled.'],
            ['key' => 'paypal', 'label' => 'PayPal', 'description' => 'Start the sandbox checkout.'],
            ['key' => 'verify', 'label' => 'Verification', 'description' => 'Server-side payment verification.'],
            ['key' => 'result', 'label' => 'Result', 'description' => 'The verified payment result.'],
            ['key' => 'order', 'label' => 'Order', 'description' => 'The order created after payment.'],
        ];

        $currentStep = match (true) {
            $order !== null => 'order',
            $payment?->status === PaymentStatus::Captured => 'result',
            $payment !== null => 'verify',
            $canPay => 'paypal',
            default => 'agreement',
        };

        $currentIndex = array_search($currentStep, array_column($steps, 'key'), true);
        $completedSteps = array_slice(array_column($steps, 'key'), 0, max(0, (int) $currentIndex));

        return Inertia::render('checkout/show', [
            'transaction' => $transaction,
            'agreement' => $agreement,
            'payment' => $payment,
            'order' => $order,
            'canPay' => $canPay,
            'steps' => $steps,
            'currentStep' => $currentStep,
            'completedSteps' => $completedSteps,
        ]);
    }
}
