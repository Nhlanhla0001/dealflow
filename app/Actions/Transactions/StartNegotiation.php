<?php

namespace App\Actions\Transactions;

use App\Enums\NegotiationStatus;
use App\Enums\TransactionState;
use App\Models\NegotiationSession;
use App\Models\Transaction;
use App\Services\NegotiationCoordinator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StartNegotiation
{
    public function __construct(private readonly NegotiationCoordinator $coordinator) {}

    public function handle(Transaction $transaction): NegotiationSession
    {
        if ($transaction->state !== TransactionState::Draft) {
            throw new InvalidArgumentException('Negotiation can only start from a draft transaction.');
        }

        if (! $transaction->offer()->exists()) {
            throw new InvalidArgumentException('The seller must submit an offer before negotiation can start.');
        }

        if ($transaction->negotiationSession()->exists()) {
            throw new InvalidArgumentException('Negotiation has already started for this transaction.');
        }

        $session = DB::transaction(function () use ($transaction): NegotiationSession {
            $session = $transaction->negotiationSession()->create([
                'status' => NegotiationStatus::Pending,
                'round' => 0,
                'max_rounds' => 5,
            ]);

            $transaction->transitionTo(TransactionState::Negotiating);

            return $session;
        });

        $this->coordinator->run($session->fresh());

        return $session->fresh();
    }
}
