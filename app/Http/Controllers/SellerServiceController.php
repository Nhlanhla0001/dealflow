<?php

namespace App\Http\Controllers;

use App\Enums\ServiceStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Service;
use App\Models\WizardDraft;
use App\Services\WizardDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SellerServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()
            ->where('seller_id', $request->user()->id)
            ->with('category:id,name,slug')
            ->latest()
            ->get();

        return Inertia::render('seller/services/index', [
            'services' => $services,
            'canCreate' => $request->user()->can('create', Service::class),
        ]);
    }

    public function create(Request $request, WizardDraftService $wizards): RedirectResponse
    {
        $this->authorize('create', Service::class);

        $draft = $wizards->start($request->user(), 'seller_listing');

        return to_route('seller.services.wizard', $draft);
    }

    public function wizard(
        Request $request,
        WizardDraft $draft,
        WizardDraftService $wizards,
    ): Response {
        $this->authorize('view', $draft);

        abort_unless($draft->wizard_type === 'seller_listing', 404);

        return Inertia::render('seller/services/wizard', [
            'draft' => $wizards->payload($draft),
            'categories' => Category::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'slug']),
            'expired' => $draft->isExpired(),
            'servicesUrl' => route('seller.services.index'),
        ]);
    }

    public function edit(
        Request $request,
        Service $service,
        WizardDraftService $wizards,
    ): RedirectResponse {
        $this->authorize('update', $service);

        $draft = $wizards->startForService($request->user(), $service);

        return to_route('seller.services.wizard', $draft);
    }

    public function status(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

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
            'action' => 'service.status_changed',
            'metadata' => [
                'service_id' => $service->id,
                'status' => $status->value,
            ],
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Service status updated.'),
        ]);

        return back();
    }
}
