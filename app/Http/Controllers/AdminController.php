<?php

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\DisputeEvent;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'services' => Service::query()->count(),
                'transactions' => Transaction::query()->count(),
                'open_disputes' => Dispute::query()
                    ->whereIn('status', ['open', 'under_review'])
                    ->count(),
            ],
            'recentDisputes' => Dispute::query()
                ->with(['order.transaction:id,title', 'opener:id,name'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }

    public function users(): Response
    {
        return Inertia::render('admin/users', [
            'users' => User::query()
                ->withCount(['services', 'buyerTransactions', 'sellerTransactions'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function services(): Response
    {
        return Inertia::render('admin/services', [
            'services' => Service::query()
                ->with(['seller:id,name', 'category:id,name'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function transactions(): Response
    {
        return Inertia::render('admin/transactions', [
            'transactions' => Transaction::query()
                ->with(['buyer:id,name', 'seller:id,name'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function disputes(): Response
    {
        return Inertia::render('admin/disputes/index', [
            'disputes' => Dispute::query()
                ->with(['order.transaction:id,title', 'opener:id,name', 'resolver:id,name'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function dispute(Dispute $dispute): Response
    {
        $dispute->load([
            'order.transaction.buyer:id,name',
            'order.transaction.seller:id,name',
            'opener:id,name',
            'resolver:id,name',
            'events.user:id,name',
        ]);

        return Inertia::render('admin/disputes/show', [
            'dispute' => $dispute,
        ]);
    }

    public function resolveDispute(Request $request, Dispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['under_review', 'resolved', 'rejected'])],
            'resolution' => ['required', 'string', 'max:3000'],
        ]);

        $dispute->update([
            'status' => $validated['status'],
            'resolution' => $validated['resolution'],
            'resolved_by' => $validated['status'] === 'under_review'
                ? null
                : $request->user()->id,
            'resolved_at' => $validated['status'] === 'under_review' ? null : now(),
        ]);

        DisputeEvent::create([
            'dispute_id' => $dispute->id,
            'user_id' => $request->user()->id,
            'type' => $validated['status'],
            'message' => $validated['resolution'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dispute updated.']);

        return back();
    }

    public function updateService(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    ServiceStatus::Published->value,
                    ServiceStatus::Unpublished->value,
                    ServiceStatus::Archived->value,
                ]),
            ],
        ]);

        $status = ServiceStatus::from($validated['status']);

        $service->update([
            'status' => $status,
            'published_at' => $status === ServiceStatus::Published
                ? ($service->published_at ?? now())
                : $service->published_at,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'transaction_id' => null,
            'action' => 'admin.service_status_changed',
            'metadata' => ['service_id' => $service->id, 'status' => $status->value],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Service updated.']);

        return back();
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => [
                'required',
                Rule::in([
                    UserRole::Buyer->value,
                    UserRole::Seller->value,
                    UserRole::Both->value,
                    UserRole::Admin->value,
                ]),
            ],
        ]);

        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own role.');

        $user->update(['role' => $validated['role']]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'transaction_id' => null,
            'action' => 'admin.user_role_changed',
            'metadata' => ['user_id' => $user->id, 'role' => $validated['role']],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User role updated.']);

        return back();
    }
}
