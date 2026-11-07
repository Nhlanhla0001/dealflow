<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\AgentConfiguration;
use App\Models\PaymentOrder;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): Response
    {
        $transactions = Transaction::query()
            ->forUser($request->user())
            ->with(['buyer:id,name', 'seller:id,name', 'offer'])
            ->latest()
            ->get()
            ->each(fn (Transaction $transaction) => $transaction->hidePrivateFieldsFrom($request->user()));

        return Inertia::render('transactions/index', [
            'transactions' => $transactions,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Transaction::class);

        $sellers = User::query()
            ->whereIn('role', [
                UserRole::Seller->value,
                UserRole::Both->value,
                UserRole::Admin->value,
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $prefill = null;

        if ($request->filled('service')) {
            $service = Service::query()
                ->published()
                ->with('seller:id,name,email')
                ->where('slug', $request->string('service')->toString())
                ->first();

            if ($service !== null) {
                $prefill = [
                    'service_id' => $service->id,
                    'seller_id' => $service->seller_id,
                    'title' => $service->title,
                    'description' => $service->description,
                    'budget' => $service->price_minor / 100,
                    'delivery_days' => $service->delivery_days,
                ];
            }
        }

        return Inertia::render('transactions/create', [
            'sellers' => $sellers,
            'prefill' => $prefill,
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $transaction = Transaction::create([
            'buyer_id' => $request->user()->id,
            'seller_id' => $request->integer('seller_id'),
            'service_id' => $request->filled('service_id') ? $request->integer('service_id') : null,
            'title' => $request->string('title')->toString(),
            'description' => $request->string('description')->toString(),
            'currency' => 'USD',
            'budget_minor' => $request->integer('budget_minor'),
            'delivery_days' => $request->integer('delivery_days'),
        ]);

        $this->audit->record($request->user(), $transaction, 'transaction.created', [
            'budget_minor' => $transaction->budget_minor,
            'delivery_days' => $transaction->delivery_days,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Transaction created.'),
        ]);

        return to_route('transactions.show', $transaction);
    }

    public function show(Request $request, Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        $transaction->load([
            'buyer:id,name,email',
            'seller:id,name,email',
            'service:id,title,slug',
            'offer',
            'negotiationSession.messages',
            'latestAgreement.approvals',
            'agentConfigurations',
            'paymentOrders',
            'order',
            'auditLogs' => fn ($query) => $query->latest()->limit(20),
        ])->hidePrivateFieldsFrom($request->user());

        $user = $request->user();

        $transaction->agentConfigurations->each(
            function (AgentConfiguration $configuration) use ($user): void {
                if ($configuration->user_id !== $user->id) {
                    $configuration->makeHidden([
                        'constraints',
                        'objective',
                        'spending_limit_minor',
                        'billing_method',
                    ]);
                }
            },
        );

        $transaction->paymentOrders->each(
            function (PaymentOrder $paymentOrder) use ($user, $transaction): void {
                if ($transaction->seller_id === $user->id) {
                    $paymentOrder->makeHidden('paypal_approval_url');
                }
            },
        );

        $agreement = $transaction->latestAgreement;

        return Inertia::render('transactions/show', [
            'transaction' => $transaction,
            'can' => [
                'update' => $request->user()->can('update', $transaction),
                'delete' => $request->user()->can('delete', $transaction),
                'approve' => $agreement !== null && $request->user()->can('approve', $agreement),
            ],
            'hasApproved' => $agreement !== null
                && $agreement->approvals->contains('user_id', $request->user()->id),
        ]);
    }
}
