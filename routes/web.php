<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgentConfigurationController;
use App\Http\Controllers\AgreementApprovalController;
use App\Http\Controllers\AiUsageController;
use App\Http\Controllers\BuyerRequestController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\SellerServiceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WizardDraftController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'home'])->name('home');
Route::get('services', [MarketplaceController::class, 'index'])->name('services.index');
Route::get('services/{service}', [MarketplaceController::class, 'show'])->name('services.show');
Route::get('categories/{category}', [MarketplaceController::class, 'category'])->name('categories.show');
Route::get('sellers/{user}', [MarketplaceController::class, 'seller'])->name('sellers.show');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::middleware('auth')->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');

    Route::patch('wizard-drafts/{draft}', [WizardDraftController::class, 'update'])
        ->middleware('throttle:60,1')
        ->name('wizard-drafts.update');
    Route::post('wizard-drafts/{draft}/step', [WizardDraftController::class, 'step'])
        ->middleware('throttle:60,1')
        ->name('wizard-drafts.step');
    Route::post('wizard-drafts/{draft}/publish', [WizardDraftController::class, 'publish'])
        ->middleware('throttle:20,1')
        ->name('wizard-drafts.publish');
    Route::delete('wizard-drafts/{draft}', [WizardDraftController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('wizard-drafts.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('ai/usage', [AiUsageController::class, 'index'])->name('ai.usage');
    Route::post('ai/credits', [AiUsageController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('ai.credits.store');

    Route::get('disputes/{dispute}', [DisputeController::class, 'show'])
        ->name('disputes.show');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/start', [OrderController::class, 'start'])->name('orders.start');
    Route::post('orders/{order}/progress', [OrderController::class, 'progress'])->name('orders.progress');
    Route::post('orders/{order}/deliver', [OrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('orders/{order}/accept', [OrderController::class, 'accept'])->name('orders.accept');
    Route::post('orders/{order}/revision', [OrderController::class, 'revision'])->name('orders.revision');
    Route::post('orders/{order}/dispute', [OrderController::class, 'dispute'])->name('orders.dispute');

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('transactions/{transaction}/offer', [OfferController::class, 'store'])
        ->name('transactions.offer.store');
    Route::post('transactions/{transaction}/negotiate', [NegotiationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('transactions.negotiate');
    Route::post('agreements/{agreement}/approve', [AgreementApprovalController::class, 'store'])
        ->name('agreements.approve');
    Route::post('agreements/{agreement}/reject', [AgreementApprovalController::class, 'reject'])
        ->name('agreements.reject');

    Route::get('transactions/{transaction}/checkout', [CheckoutController::class, 'show'])
        ->name('checkout.show');
    Route::post('transactions/{transaction}/checkout', [PaymentController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('transactions.checkout');

    Route::get('transactions/{transaction}/agent', [AgentConfigurationController::class, 'create'])
        ->name('agents.create');
    Route::get('agent-drafts/{draft}', [AgentConfigurationController::class, 'wizard'])
        ->name('agents.wizard');

    Route::get('buyer/requests/new', [BuyerRequestController::class, 'create'])
        ->name('buyer.requests.create');
    Route::get('buyer/requests/drafts/{draft}', [BuyerRequestController::class, 'wizard'])
        ->name('buyer.requests.wizard');

    Route::get('seller/services', [SellerServiceController::class, 'index'])
        ->name('seller.services.index');
    Route::get('seller/services/new', [SellerServiceController::class, 'create'])
        ->name('seller.services.create');
    Route::get('seller/services/drafts/{draft}', [SellerServiceController::class, 'wizard'])
        ->name('seller.services.wizard');
    Route::get('seller/services/{service:id}/edit', [SellerServiceController::class, 'edit'])
        ->name('seller.services.edit');
    Route::patch('seller/services/{service:id}/status', [SellerServiceController::class, 'status'])
        ->name('seller.services.status');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('users', [AdminController::class, 'users'])->name('users');
        Route::patch('users/{user}/role', [AdminController::class, 'updateUserRole'])
            ->name('users.role');
        Route::get('services', [AdminController::class, 'services'])->name('services');
        Route::patch('services/{service:id}/status', [AdminController::class, 'updateService'])
            ->name('services.status');
        Route::get('transactions', [AdminController::class, 'transactions'])->name('transactions');
        Route::get('disputes', [AdminController::class, 'disputes'])->name('disputes');
        Route::get('disputes/{dispute}', [AdminController::class, 'dispute'])->name('disputes.show');
        Route::post('disputes/{dispute}/resolve', [AdminController::class, 'resolveDispute'])
            ->name('disputes.resolve');
    });

Route::get('payments/{paymentOrder}/return', [PaymentController::class, 'return'])
    ->middleware('signed')
    ->name('payments.return');
Route::get('payments/{paymentOrder}/cancel', [PaymentController::class, 'cancel'])
    ->middleware('signed')
    ->name('payments.cancel');

Route::post('webhooks/paypal', PayPalWebhookController::class)
    ->name('webhooks.paypal');

require __DIR__.'/settings.php';
