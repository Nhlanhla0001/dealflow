<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Transaction;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function store(StoreOfferRequest $request, Transaction $transaction): RedirectResponse
    {
        $offer = $transaction->offer()->updateOrCreate([], [
            'seller_id' => $request->user()->id,
            'price_minor' => $request->integer('price_minor'),
            'currency' => $transaction->currency,
            'delivery_days' => $request->integer('delivery_days'),
            'terms' => $request->filled('terms') ? $request->string('terms')->toString() : null,
            'status' => OfferStatus::Active,
        ]);

        $transaction->update([
            'seller_offer_minor' => $offer->price_minor,
            'seller_floor_minor' => $request->integer('floor_minor'),
            'seller_delivery_days' => $offer->delivery_days,
        ]);

        $this->audit->record($request->user(), $transaction, 'offer.submitted', [
            'offer_id' => $offer->id,
            'price_minor' => $offer->price_minor,
            'delivery_days' => $offer->delivery_days,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Offer submitted. The buyer can now start the negotiation.'),
        ]);

        return to_route('transactions.show', $transaction);
    }
}
