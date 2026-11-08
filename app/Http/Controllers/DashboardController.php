<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $transactions = Transaction::query()
            ->forUser($user)
            ->with(['buyer:id,name', 'seller:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->each(fn (Transaction $transaction) => $transaction->hidePrivateFieldsFrom($user));

        return Inertia::render('dashboard', [
            'transactions' => $transactions,
            'stats' => [
                'open' => Transaction::query()->forUser($user)->open()->count(),
                'negotiating' => Transaction::query()->forUser($user)->where('state', 'negotiating')->count(),
                'awaiting_approvals' => Transaction::query()->forUser($user)->where('state', 'awaiting_approvals')->count(),
                'paid' => Transaction::query()->forUser($user)->where('state', 'paid')->count(),
            ],
        ]);
    }
}
