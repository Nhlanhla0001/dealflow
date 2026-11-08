<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Transaction;
use App\Models\WizardDraft;
use App\Services\WizardDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuyerRequestController extends Controller
{
    public function create(Request $request, WizardDraftService $wizards): RedirectResponse
    {
        $this->authorize('create', Transaction::class);

        $validated = $request->validate([
            'service' => ['required', 'string', 'max:255'],
        ]);

        $service = Service::query()
            ->published()
            ->where('slug', $validated['service'])
            ->firstOrFail();

        $draft = $wizards->startForBuyerRequest($request->user(), $service);

        return to_route('buyer.requests.wizard', $draft);
    }

    public function wizard(
        WizardDraft $draft,
        WizardDraftService $wizards,
    ): Response {
        $this->authorize('view', $draft);

        abort_unless($draft->wizard_type === 'buyer_request', 404);

        return Inertia::render('buyer/requests/wizard', [
            'draft' => $wizards->payload($draft),
            'services' => Service::query()
                ->published()
                ->with(['seller:id,name', 'category:id,name,slug'])
                ->latest('published_at')
                ->limit(100)
                ->get(),
            'backUrl' => '/services',
        ]);
    }
}
