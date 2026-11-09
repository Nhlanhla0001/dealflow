<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Enums\TransactionState;
use App\Models\Agreement;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AgreementService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * @param  array<string, mixed>  $negotiationSummary
     */
    public function create(
        Transaction $transaction,
        int $priceMinor,
        int $deliveryDays,
        array $negotiationSummary = [],
    ): Agreement {
        return DB::transaction(function () use ($transaction, $priceMinor, $deliveryDays, $negotiationSummary): Agreement {
            $transaction->refresh();
            $transaction->loadMissing(['buyer', 'seller']);

            if (in_array($transaction->state, [
                TransactionState::PaymentPending,
                TransactionState::Paid,
                TransactionState::Cancelled,
                TransactionState::Failed,
            ], true)) {
                throw new InvalidArgumentException(
                    'A new agreement cannot be created once payment has started or the deal is closed.'
                );
            }

            $version = (int) $transaction->agreements()->max('version') + 1;

            $terms = [
                'buyer' => [
                    'id' => $transaction->buyer_id,
                    'name' => $transaction->buyer?->name,
                ],
                'seller' => [
                    'id' => $transaction->seller_id,
                    'name' => $transaction->seller?->name,
                ],
                'scope' => $transaction->title,
                'description' => $transaction->description,
                'price_minor' => $priceMinor,
                'currency' => $transaction->currency,
                'delivery_days' => $deliveryDays,
                'payment_terms' => 'PayPal sandbox checkout after both parties approve.',
                'cancellation' => 'Either party may cancel before payment.',
                'version' => $version,
                'generated_at' => now()->toIso8601String(),
                'negotiation_summary' => $negotiationSummary,
            ];

            $agreement = $transaction->agreements()->create([
                'version' => $version,
                'price_minor' => $priceMinor,
                'currency' => $transaction->currency,
                'delivery_days' => $deliveryDays,
                'terms' => $terms,
                'status' => AgreementStatus::AwaitingApprovals,
                'hash' => hash('sha256', json_encode($terms, JSON_THROW_ON_ERROR)),
            ]);

            $transaction->agreements()
                ->whereKeyNot($agreement->id)
                ->whereIn('status', [
                    AgreementStatus::AwaitingApprovals->value,
                    AgreementStatus::Approved->value,
                ])
                ->update(['status' => AgreementStatus::Superseded->value]);

            if ($transaction->state === TransactionState::Approved) {
                $transaction->transitionTo(TransactionState::AwaitingApprovals);
            }

            if ($transaction->state === TransactionState::Negotiating) {
                $transaction->transitionTo(TransactionState::AgreementProposed);
            }

            if ($transaction->state === TransactionState::AgreementProposed) {
                $transaction->transitionTo(TransactionState::AwaitingApprovals);
            }

            $this->audit->record($transaction->buyer, $transaction, 'agreement.created', [
                'agreement_id' => $agreement->id,
                'version' => $version,
                'price_minor' => $priceMinor,
                'delivery_days' => $deliveryDays,
            ]);

            return $agreement;
        });
    }
}
