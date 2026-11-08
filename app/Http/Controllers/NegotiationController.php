<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\StartNegotiation;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;

class NegotiationController extends Controller
{
    public function store(
        Request $request,
        Transaction $transaction,
        StartNegotiation $startNegotiation,
    ): RedirectResponse {
        $this->authorize('startNegotiation', $transaction);

        try {
            $startNegotiation->handle($transaction);
        } catch (InvalidArgumentException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->getMessage(),
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Negotiation complete. The agreement is ready for both parties to approve.'),
        ]);

        return to_route('transactions.show', $transaction);
    }
}
