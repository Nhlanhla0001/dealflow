<?php

namespace App\Http\Controllers;

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

        if ($order->status !== 'paid') {
            return $this->invalid('Only a paid order can be started.');
        }

        DB::transaction(function () use ($order, $request): void {
            $order->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
            $this->event($order, $request, 'started', 'Seller started work.');
        });

        return $this->success('Order started.');
    }

    public function progress(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->seller_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if (! in_array($order->status, ['in_progress', 'revision_requested'], true)) {
            return $this->invalid('Progress can only be added while work is in progress.');
        }

        $this->event($order, $request, 'progress', $validated['message']);

        return $this->success('Progress update added.');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->seller_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
            'delivery_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if (! in_array($order->status, ['in_progress', 'revision_requested'], true)) {
            return $this->invalid('There is no active work to deliver.');
        }

        DB::transaction(function () use ($order, $request, $validated): void {
            $order->update([
                'status' => 'delivered',
                'delivery_message' => $validated['message'],
                'delivery_url' => $validated['delivery_url'] ?? null,
                'delivered_at' => now(),
            ]);
            $this->event($order, $request, 'delivered', $validated['message']);
        });

        return $this->success('Delivery submitted. The buyer can now review it.');
    }

    public function accept(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->buyer_id === $request->user()->id, 403);

        if ($order->status !== 'delivered') {
            return $this->invalid('Only a delivered order can be accepted.');
        }

        DB::transaction(function () use ($order, $request): void {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->event($order, $request, 'accepted', 'Buyer accepted the delivery.');
        });

        return $this->success('Order completed.');
    }

    public function revision(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);
        abort_unless($order->buyer_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if ($order->status !== 'delivered') {
            return $this->invalid('Only a delivered order can be sent back for revision.');
        }

        $limit = $order->transaction->service?->revisions ?? 1;

        if ($order->revisions_used >= $limit) {
            return $this->invalid('The revision limit for this order has been reached.');
        }

        DB::transaction(function () use ($order, $request, $validated): void {
            $order->update([
                'status' => 'revision_requested',
                'revisions_used' => $order->revisions_used + 1,
            ]);
            $this->event($order, $request, 'revision_requested', $validated['message']);
        });

        return $this->success('Revision requested.');
    }

    public function dispute(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        if (! in_array($order->status, ['paid', 'in_progress', 'delivered', 'revision_requested'], true)) {
            return $this->invalid('This order cannot be disputed in its current state.');
        }

        DB::transaction(function () use ($order, $request, $validated): void {
            $order->update(['status' => 'disputed']);
            $this->event($order, $request, 'disputed', $validated['message'], [
                'reason' => $validated['reason'],
            ]);
        });

        return $this->success('Dispute opened.');
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
