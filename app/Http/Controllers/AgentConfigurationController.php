<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\WizardDraft;
use App\Services\AiCreditService;
use App\Services\WizardDraftService;
use App\Support\Ai\ProviderCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgentConfigurationController extends Controller
{
    public function create(
        Request $request,
        Transaction $transaction,
        WizardDraftService $wizards,
    ): RedirectResponse {
        $this->authorize('view', $transaction);

        $role = match ($request->user()->id) {
            $transaction->buyer_id => 'buyer',
            $transaction->seller_id => 'seller',
            default => abort(403),
        };

        $draft = $wizards->startForAgent($request->user(), $transaction, $role);

        return to_route('agents.wizard', $draft);
    }

    public function wizard(
        WizardDraft $draft,
        WizardDraftService $wizards,
        AiCreditService $credits,
        ProviderCatalog $providers,
    ): Response {
        $this->authorize('view', $draft);

        abort_unless($draft->wizard_type === 'agent_config', 404);

        $transaction = Transaction::query()
            ->with(['buyer:id,name', 'seller:id,name', 'offer'])
            ->findOrFail($draft->context_id);

        return Inertia::render('agents/wizard', [
            'draft' => $wizards->payload($draft),
            'transaction' => $transaction,
            'providers' => $providers->providers(),
            'balanceMinor' => $credits->balance($draft->user),
            'backUrl' => route('transactions.show', $transaction),
        ]);
    }
}
