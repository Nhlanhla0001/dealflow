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

        $configurations = $transaction->agentConfigurations()
            ->where('status', 'active')
            ->get();

        if ($transaction->negotiation_mode === 'ai' && $configurations->isNotEmpty()) {
            $roles = $configurations->pluck('role')->all();

            if (! in_array('buyer', $roles, true) || ! in_array('seller', $roles, true)) {
                throw new InvalidArgumentException('Both parties must activate their AI agents before AI-assisted negotiation.');
            }
        }

        $maxRounds = $configurations->isEmpty()
            ? 5
            : max(1, min(5, (int) $configurations->min('max_turns')));

        $session = DB::transaction(function () use ($transaction, $maxRounds): NegotiationSession {
            $session = $transaction->negotiationSession()->create([
                'status' => NegotiationStatus::Pending,
                'round' => 0,
                'max_rounds' => $maxRounds,
            ]);

            $transaction->transitionTo(TransactionState::Negotiating);

            return $session;
        });

        if ($transaction->negotiation_mode !== 'manual') {
            $this->coordinator->run($session->fresh());
        }

        return $session->fresh();
    }
}
