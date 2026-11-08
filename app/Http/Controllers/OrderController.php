<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\DisputeEvent;
use App\Models\Order;
use App\Models\OrderEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->where(function ($query) use ($request): void {
                $query->where('buyer_id', $request->user()->id)
                    ->orWhere('seller_id', $request->user()->id);
            })
            ->with(['transaction:id,title', 'buyer:id,name', 'seller:id,name'])
            ->latest()
            ->get();

        return Inertia::render('orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load([
            'transaction.buyer:id,name',
            'transaction.seller:id,name',
            'transaction.service:id,title,revisions',
            'events.user:id,name',
            'dispute.events.user:id,name',
        ]);

        return Inertia::render('orders/show', [
            'order' => $order,
            'isBuyer' => $order->buyer_id === $request->user()->id,
            'isSeller' => $order->seller_id === $request->user()->id,
            'revisionLimit' => $order->transaction->service?->revisions ?? 1,
        ]);
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->seller_id === $request->user()->id, 403);

        return DB::transaction(function () use ($order, $request): RedirectResponse {
            $fresh = $this->lockedOrder($order);

            if ($fresh->status !== 'paid') {
                return $this->invalid('Only a paid order can be started.');
            }

            $fresh->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
            $this->event($fresh, $request, 'started', 'Seller started work.');

            return $this->success('Order started.');
        });
    }

    public function progress(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->seller_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($order, $request, $validated): RedirectResponse {
            $fresh = $this->lockedOrder($order);

            if (! in_array($fresh->status, ['in_progress', 'revision_requested'], true)) {
                return $this->invalid('Progress can only be added while work is in progress.');
            }

            $this->event($fresh, $request, 'progress', $validated['message']);

            return $this->success('Progress update added.');
        });
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->seller_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
            'delivery_url' => ['nullable', 'url', 'max:2048'],
        ]);

        return DB::transaction(function () use ($order, $request, $validated): RedirectResponse {
            $fresh = $this->lockedOrder($order);

            if (! in_array($fresh->status, ['in_progress', 'revision_requested'], true)) {
                return $this->invalid('There is no active work to deliver.');
            }

            $fresh->update([
                'status' => 'delivered',
                'delivery_message' => $validated['message'],
                'delivery_url' => $validated['delivery_url'] ?? null,
                'delivered_at' => now(),
            ]);
            $this->event($fresh, $request, 'delivered', $validated['message']);

            return $this->success('Delivery submitted. The buyer can now review it.');
        });
    }

    public function accept(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->buyer_id === $request->user()->id, 403);

        return DB::transaction(function () use ($order, $request): RedirectResponse {
            $fresh = $this->lockedOrder($order);

            if ($fresh->status !== 'delivered') {
                return $this->invalid('Only a delivered order can be accepted.');
            }

            $fresh->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->event($fresh, $request, 'accepted', 'Buyer accepted the delivery.');

            return $this->success('Order completed.');
        });
    }

    public function revision(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($order, $request, $validated): RedirectResponse {
            $fresh = $this->lockedOrder($order);

            if ($fresh->status !== 'delivered') {
                return $this->invalid('Only a delivered order can be sent back for revision.');
            }

            $limit = $fresh->transaction->service?->revisions ?? 1;

            if ($fresh->revisions_used >= $limit) {
                return $this->invalid('The revision limit for this order has been reached.');
            }

            $fresh->update([
                'status' => 'revision_requested',
                'revisions_used' => $fresh->revisions_used + 1,
            ]);
            $this->event($fresh, $request, 'revision_requested', $validated['message']);

            return $this->success('Revision requested.');
        });
    }

    public function dispute(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:3000'],
            'evidence_url' => ['nullable', 'url', 'max:2048'],
        ]);

        return DB::transaction(function () use ($order, $request, $validated): RedirectResponse {
            $fresh = $this->lockedOrder($order);

            if (! in_array($fresh->status, ['paid', 'in_progress', 'delivered', 'revision_requested'], true)) {
                return $this->invalid('This order cannot be disputed in its current state.');
            }

            $fresh->update(['status' => 'disputed']);

            $dispute = Dispute::create([
                'order_id' => $fresh->id,
                'opened_by' => $request->user()->id,
                'reason' => $validated['reason'],
                'description' => $validated['message'],
                'evidence_url' => $validated['evidence_url'] ?? null,
                'status' => 'open',
            ]);

            DisputeEvent::create([
                'dispute_id' => $dispute->id,
                'user_id' => $request->user()->id,
                'type' => 'opened',
                'message' => $validated['message'],
                'metadata' => ['reason' => $validated['reason']],
            ]);

            $this->event($fresh, $request, 'disputed', $validated['message'], [
                'reason' => $validated['reason'],
                'dispute_id' => $dispute->id,
            ]);

            return $this->success('Dispute opened.');
        });
    }

    private function lockedOrder(Order $order): Order
    {
        return Order::query()->lockForUpdate()->findOrFail($order->id);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function event(
        Order $order,
        Request $request,
        string $type,
        ?string $message = null,
        array $metadata = [],
    ): void {
        OrderEvent::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'message' => $message,
            'metadata' => $metadata,
        ]);
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
