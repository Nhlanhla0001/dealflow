<?php

namespace App\Http\Controllers;

use App\Enums\NegotiationStatus;
use App\Enums\TransactionState;
use App\Models\Transaction;
use App\Notifications\TermsReadyForApproval;
use App\Services\AgreementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class ManualNegotiationController extends Controller
{
    public function offer(
        Request $request,
        Transaction $transaction,
    ): RedirectResponse {
        $this->authorize('view', $transaction);

        $role = $this->roleFor($request, $transaction);
        abort_if($role === null, 403);

        if (! in_array($transaction->state, [TransactionState::Draft, TransactionState::Negotiating], true)) {
            return $this->invalid('Manual offers are only available before an agreement is proposed.');
        }

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $priceMinor = (int) round(((float) $validated['price']) * 100);
        $deliveryDays = (int) $validated['delivery_days'];

        if ($role === 'buyer' && $priceMinor > $transaction->budget_minor) {
            return $this->invalid('Your offer cannot exceed the transaction budget.');
        }

        if ($role === 'seller') {
            $floor = (int) ($transaction->seller_floor_minor
                ?? $transaction->seller_offer_minor
                ?? $transaction->offer()->value('price_minor')
                ?? 0);

            if ($priceMinor < $floor) {
                return $this->invalid('Your offer cannot go below your private floor.');
            }
        }

        DB::transaction(function () use ($transaction, $role, $priceMinor, $deliveryDays, $validated): void {
            $session = $transaction->negotiationSession()->firstOrCreate([], [
                'status' => NegotiationStatus::Active,
                'round' => 0,
                'max_rounds' => 10,
                'started_at' => now(),
            ]);

            $round = (int) $session->messages()->max('round') + 1;

            $session->messages()->create([
                'agent' => $role,
                'round' => $round,
                'action' => 'counteroffer',
                'price_minor' => $priceMinor,
                'currency' => $transaction->currency,
                'delivery_days' => $deliveryDays,
                'reason' => $validated['reason'],
            ]);

            $session->update([
                'status' => NegotiationStatus::Active,
                'round' => $round,
            ]);

            if ($transaction->state === TransactionState::Draft) {
                $transaction->transitionTo(TransactionState::Negotiating);
            }
        });

        return $this->success('Offer recorded.');
    }

    public function accept(
        Request $request,
        Transaction $transaction,
        AgreementService $agreements,
    ): RedirectResponse {
        $this->authorize('view', $transaction);

        $role = $this->roleFor($request, $transaction);
        abort_if($role === null, 403);

        if ($transaction->state !== TransactionState::Negotiating) {
            return $this->invalid('There is no active manual negotiation to accept.');
        }

        $session = $transaction->negotiationSession()->first();

        if ($session === null) {
            return $this->invalid('There is no manual negotiation to accept.');
        }

        $latest = $session->messages()->latest('round')->latest('id')->first();

        if ($latest === null || $latest->agent === $role) {
            return $this->invalid('Wait for the other party to make an offer before accepting.');
        }

        $priceMinor = (int) $latest->price_minor;
        $deliveryDays = (int) $latest->delivery_days;

        if ($priceMinor > $transaction->budget_minor) {
            return $this->invalid('The latest offer exceeds the transaction budget.');
        }

        $floor = (int) ($transaction->seller_floor_minor
            ?? $transaction->seller_offer_minor
            ?? $transaction->offer()->value('price_minor')
            ?? 0);

        if ($priceMinor < $floor) {
            return $this->invalid('The latest offer is below the seller floor.');
        }

        $agreement = $agreements->create($transaction, $priceMinor, $deliveryDays, [
            'mode' => 'manual',
            'accepted_by' => $role,
        ]);

        $session->update([
            'status' => NegotiationStatus::Agreed,
            'completed_at' => now(),
        ]);

        Notification::send(
            [$transaction->buyer, $transaction->seller],
            new TermsReadyForApproval($agreement),
        );

        return $this->success('Offer accepted. Both parties can now approve the agreement.');
    }

    private function roleFor(Request $request, Transaction $transaction): ?string
    {
        return match ($request->user()->id) {
            $transaction->buyer_id => 'buyer',
            $transaction->seller_id => 'seller',
            default => null,
        };
    }

    private function success(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function invalid(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return back();
    }
}
