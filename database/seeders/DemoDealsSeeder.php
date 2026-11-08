<?php

namespace Database\Seeders;

use App\Enums\AgreementStatus;
use App\Enums\NegotiationAction;
use App\Enums\NegotiationStatus;
use App\Enums\OfferStatus;
use App\Enums\PaymentStatus;
use App\Enums\TransactionState;
use App\Models\Agreement;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TermsReadyForApproval;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDealsSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::query()->where('email', 'buyer@dealflow.test')->firstOrFail();
        $seller = User::query()->where('email', 'seller@dealflow.test')->firstOrFail();

        $paid = $this->createDeal(
            buyer: $buyer,
            seller: $seller,
            title: 'Business website build',
            description: 'A seven-page marketing site with a contact form, CMS, and launch support.',
            budgetMinor: 50000,
            deliveryDays: 7,
            sellerOfferMinor: 65000,
            sellerFloorMinor: 45000,
            sellerDeliveryDays: 10,
            sellerTerms: 'Two revision rounds and payment on delivery.',
            state: TransactionState::Paid,
            sessionRound: 2,
            messages: [
                ['buyer', 1, NegotiationAction::Counteroffer, 50000, 'Countered at the approved budget maximum while keeping the delivery window.'],
                ['seller', 2, NegotiationAction::Accept, 50000, 'Accepted the terms because they meet the seller minimum and delivery range.'],
            ],
            agreementStatus: AgreementStatus::Approved,
            agreementPriceMinor: 50000,
            agreementDeliveryDays: 7,
            approvals: true,
            createdAt: now()->subMinutes(4),
        );

        $this->attachPaidPayment($paid);

        $approved = $this->createDeal(
            buyer: $buyer,
            seller: $seller,
            title: 'E-commerce checkout redesign',
            description: 'Checkout audit, redesign, and implementation for a Shopify storefront.',
            budgetMinor: 75000,
            deliveryDays: 10,
            sellerOfferMinor: 80000,
            sellerFloorMinor: 60000,
            sellerDeliveryDays: 10,
            sellerTerms: 'Checkout audit, redesign, and implementation.',
            state: TransactionState::Approved,
            sessionRound: 2,
            messages: [
                ['buyer', 1, NegotiationAction::Counteroffer, 75000, 'Countered at the approved budget maximum while keeping the delivery window.'],
                ['seller', 2, NegotiationAction::Accept, 75000, 'Accepted the terms because they meet the seller minimum and delivery range.'],
            ],
            agreementStatus: AgreementStatus::Approved,
            agreementPriceMinor: 75000,
            agreementDeliveryDays: 10,
            approvals: true,
            createdAt: now()->subMinutes(3),
        );

        $this->notifyTerms($approved, $buyer, read: true);
        $this->notifyTerms($approved, $seller, read: true);

        $landing = $this->createDeal(
            buyer: $buyer,
            seller: $seller,
            title: 'AI negotiation demo — landing page',
            description: 'A landing page, copy, build, and two rounds of launch QA.',
            budgetMinor: 50000,
            deliveryDays: 7,
            sellerOfferMinor: 65000,
            sellerFloorMinor: 45000,
            sellerDeliveryDays: 10,
            sellerTerms: 'Two revision rounds and launch QA.',
            state: TransactionState::AwaitingApprovals,
            sessionRound: 4,
            messages: [
                ['buyer', 1, NegotiationAction::Counteroffer, 42000, 'The initial offer exceeds our maximum budget. We are proposing a price of 42,000 to begin negotiations closer to our limit.'],
                ['seller', 2, NegotiationAction::Counteroffer, 58000, 'While I appreciate the offer, 42,000 does not reflect the value and expertise required for this launch.'],
                ['buyer', 3, NegotiationAction::Counteroffer, 50000, 'Cannot exceed the approved budget; this is the final proposal.'],
                ['seller', 4, NegotiationAction::Accept, 50000, 'Accepted the final proposal because it meets the seller minimum.'],
            ],
            agreementStatus: AgreementStatus::AwaitingApprovals,
            agreementPriceMinor: 50000,
            agreementDeliveryDays: 7,
            approvals: false,
            createdAt: now()->subMinutes(2),
            agentRuns: [
                ['buyer', 'succeeded'],
                ['seller', 'succeeded'],
            ],
        );

        $this->notifyTerms($landing, $buyer, read: false);
        $this->notifyTerms($landing, $seller, read: true);

        $productLaunch = $this->createDeal(
            buyer: $buyer,
            seller: $seller,
            title: 'AI negotiation demo 2 — product launch',
            description: 'A product launch page, copy, build, and two rounds of launch QA.',
            budgetMinor: 50000,
            deliveryDays: 7,
            sellerOfferMinor: 65000,
            sellerFloorMinor: 45000,
            sellerDeliveryDays: 10,
            sellerTerms: 'Two revision rounds and launch QA.',
            state: TransactionState::AwaitingApprovals,
            sessionRound: 4,
            messages: [
                ['buyer', 1, NegotiationAction::Counteroffer, 40000, 'The proposed price exceeds our budget of 50,000. We are offering 40,000 to begin negotiations closer to our limit while maintaining the 7-day delivery window.'],
                ['seller', 2, NegotiationAction::Counteroffer, 58000, 'We cannot accept 40,000 as it is significantly below our valuation for this launch. We are willing to lower our offer to 58,000 while keeping the 7-day delivery timeline.'],
                ['buyer', 3, NegotiationAction::Counteroffer, 50000, 'This is our final offer and represents the absolute maximum budget allocated for this project.'],
                ['seller', 4, NegotiationAction::Accept, 50000, 'Accepted the final proposal because it meets the seller minimum.'],
            ],
            agreementStatus: AgreementStatus::AwaitingApprovals,
            agreementPriceMinor: 50000,
            agreementDeliveryDays: 7,
            approvals: false,
            createdAt: now()->subMinute(),
            agentRuns: [
                ['buyer', 'succeeded'],
                ['seller', 'succeeded'],
                ['buyer', 'succeeded'],
            ],
        );

        $this->notifyTerms($productLaunch, $buyer, read: false);
        $this->notifyTerms($productLaunch, $seller, read: false);
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: NegotiationAction, 3: int, 4: string}>  $messages
     * @param  array<int, array{0: string, 1: string}>  $agentRuns
     */
    private function createDeal(
        User $buyer,
        User $seller,
        string $title,
        string $description,
        int $budgetMinor,
        int $deliveryDays,
        int $sellerOfferMinor,
        int $sellerFloorMinor,
        int $sellerDeliveryDays,
        string $sellerTerms,
        TransactionState $state,
        int $sessionRound,
        array $messages,
        AgreementStatus $agreementStatus,
        int $agreementPriceMinor,
        int $agreementDeliveryDays,
        bool $approvals,
        \DateTimeInterface $createdAt,
        array $agentRuns = [],
    ): Transaction {
        $transaction = Transaction::query()->updateOrCreate(
            [
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'title' => $title,
            ],
            [
                'description' => $description,
                'currency' => 'USD',
                'budget_minor' => $budgetMinor,
                'seller_offer_minor' => $sellerOfferMinor,
                'seller_floor_minor' => $sellerFloorMinor,
                'delivery_days' => $deliveryDays,
                'seller_delivery_days' => $sellerDeliveryDays,
                'state' => $state,
            ],
        );

        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        $offer = $transaction->offer()->updateOrCreate([], [
            'seller_id' => $seller->id,
            'price_minor' => $sellerOfferMinor,
            'currency' => 'USD',
            'delivery_days' => $sellerDeliveryDays,
            'terms' => $sellerTerms,
            'status' => OfferStatus::Active,
        ]);

        $session = $transaction->negotiationSession()->updateOrCreate([], [
            'status' => NegotiationStatus::Agreed,
            'round' => $sessionRound,
            'max_rounds' => 5,
            'started_at' => $createdAt,
            'completed_at' => $createdAt,
        ]);

        foreach ($messages as [$agent, $round, $action, $priceMinor, $reason]) {
            $session->messages()->updateOrCreate(
                ['round' => $round, 'agent' => $agent],
                [
                    'action' => $action,
                    'price_minor' => $priceMinor,
                    'currency' => 'USD',
                    'delivery_days' => $agreementDeliveryDays,
                    'reason' => $reason,
                ],
            );
        }

        foreach ($agentRuns as [$agent, $status]) {
            $session->agentRuns()->create([
                'agent' => $agent,
                'provider' => 'gemini',
                'model' => 'gemini-3-flash-preview',
                'status' => $status,
                'input' => ['round' => $sessionRound, 'max_rounds' => 5],
                'output' => ['action' => 'counteroffer'],
                'prompt_tokens' => 420,
                'completion_tokens' => 90,
            ]);
        }

        $agreement = $transaction->agreements()->updateOrCreate(
            ['version' => 1],
            [
                'price_minor' => $agreementPriceMinor,
                'currency' => 'USD',
                'delivery_days' => $agreementDeliveryDays,
                'terms' => ['scope' => $description, 'revisions' => 2],
                'status' => $agreementStatus,
                'hash' => hash('sha256', $title.'-v1'),
            ],
        );

        if ($approvals) {
            foreach ([[$buyer, 'buyer'], [$seller, 'seller']] as [$user, $role]) {
                $agreement->approvals()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['role' => $role, 'approved_at' => $createdAt],
                );
            }
        }

        $this->audit($transaction, $buyer, 'transaction.created', [
            'budget_minor' => $budgetMinor,
            'delivery_days' => $deliveryDays,
        ]);
        $this->audit($transaction, $seller, 'offer.submitted', [
            'offer_id' => $offer->id,
            'price_minor' => $sellerOfferMinor,
            'delivery_days' => $sellerDeliveryDays,
        ]);
        $this->audit($transaction, $buyer, 'negotiation.started', [
            'session_id' => $session->id,
        ]);
        $this->audit($transaction, $buyer, 'agreement.proposed', [
            'agreement_id' => $agreement->id,
            'price_minor' => $agreementPriceMinor,
            'delivery_days' => $agreementDeliveryDays,
        ]);

        if ($approvals) {
            $this->audit($transaction, $buyer, 'agreement.approved', [
                'agreement_id' => $agreement->id,
                'role' => 'buyer',
            ]);
            $this->audit($transaction, $seller, 'agreement.approved', [
                'agreement_id' => $agreement->id,
                'role' => 'seller',
            ]);
            $this->audit($transaction, $buyer, 'agreement.fully_approved', [
                'agreement_id' => $agreement->id,
            ]);
        }

        return $transaction;
    }

    private function attachPaidPayment(Transaction $transaction): void
    {
        $agreement = $transaction->agreements()->where('version', 1)->firstOrFail();

        $paymentOrder = $transaction->paymentOrders()->updateOrCreate(
            ['paypal_order_id' => '7HB468239D4915820'],
            [
                'agreement_id' => $agreement->id,
                'status' => PaymentStatus::Captured,
                'amount_minor' => 50000,
                'currency' => 'USD',
                'capture_id' => '84437967V6387225V',
                'payload' => [
                    'id' => '7HB468239D4915820',
                    'status' => 'COMPLETED',
                    'purchase_units' => [
                        [
                            'payments' => [
                                'captures' => [
                                    ['id' => '84437967V6387225V', 'status' => 'COMPLETED'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $paymentOrder->events()->updateOrCreate(
            ['event_id' => 'WH-DEMO-CAPTURE-1'],
            [
                'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
                'payload' => [
                    'id' => 'WH-DEMO-CAPTURE-1',
                    'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
                    'resource' => [
                        'id' => '84437967V6387225V',
                        'supplementary_data' => [
                            'related_ids' => ['order_id' => '7HB468239D4915820'],
                        ],
                    ],
                ],
                'processed_at' => now()->subMinutes(4),
            ],
        );

        $this->audit($transaction, $transaction->buyer, 'payment.order_created', [
            'payment_order_id' => $paymentOrder->id,
            'paypal_order_id' => $paymentOrder->paypal_order_id,
        ]);
        $this->audit($transaction, $transaction->buyer, 'payment.captured', [
            'payment_order_id' => $paymentOrder->id,
            'capture_id' => $paymentOrder->capture_id,
        ]);

        Order::query()->firstOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'buyer_id' => $transaction->buyer_id,
                'seller_id' => $transaction->seller_id,
                'amount_minor' => 50000,
                'currency' => 'USD',
                'status' => 'paid',
                'paid_at' => now()->subMinutes(4),
            ],
        );
    }

    private function notifyTerms(Transaction $transaction, User $user, bool $read): void
    {
        $agreement = $transaction->latestAgreement;

        if (! $agreement instanceof Agreement) {
            return;
        }

        $notification = new TermsReadyForApproval($agreement);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => TermsReadyForApproval::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($notification->toArray($user), JSON_THROW_ON_ERROR),
            'read_at' => $read ? now()->subMinute() : null,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(Transaction $transaction, ?User $user, string $action, array $metadata): void
    {
        $transaction->auditLogs()->firstOrCreate(
            ['action' => $action, 'user_id' => $user?->id],
            ['metadata' => $metadata],
        );
    }
}
