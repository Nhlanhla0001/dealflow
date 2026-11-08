<?php

namespace App\Http\Controllers;

use App\Models\AiUsageRecord;
use App\Services\AiCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiUsageController extends Controller
{
    public function index(Request $request, AiCreditService $credits): Response
    {
        $user = $request->user();

        return Inertia::render('ai/usage', [
            'balanceMinor' => $credits->balance($user),
            'entries' => $user->aiCreditLedgerEntries()
                ->latest()
                ->limit(50)
                ->get(),
            'usage' => AiUsageRecord::query()
                ->whereHas(
                    'configuration',
                    fn ($query) => $query->where('user_id', $user->id),
                )
                ->with(['configuration:id,transaction_id,role,provider,model'])
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(Request $request, AiCreditService $credits): RedirectResponse
    {
        $credits->credit(
            $request->user(),
            500,
            'sandbox-credit-'.Str::uuid(),
            'Sandbox AI credit (demo only)',
            null,
            ['source' => 'demo'],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('$5.00 sandbox AI credit added.'),
        ]);

        return back();
    }
}
