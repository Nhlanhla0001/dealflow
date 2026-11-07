<?php

use App\Http\Controllers\AgreementApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('transactions/{transaction}/offer', [OfferController::class, 'store'])
        ->name('transactions.offer.store');
    Route::post('transactions/{transaction}/negotiate', [NegotiationController::class, 'store'])
        ->name('transactions.negotiate');
    Route::post('agreements/{agreement}/approve', [AgreementApprovalController::class, 'store'])
        ->name('agreements.approve');
    Route::post('agreements/{agreement}/reject', [AgreementApprovalController::class, 'reject'])
        ->name('agreements.reject');

    Route::post('transactions/{transaction}/checkout', [PaymentController::class, 'store'])
        ->name('transactions.checkout');

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

    Route::patch('wizard-drafts/{draft}', [WizardDraftController::class, 'update'])
        ->name('wizard-drafts.update');
    Route::post('wizard-drafts/{draft}/step', [WizardDraftController::class, 'step'])
        ->name('wizard-drafts.step');
    Route::post('wizard-drafts/{draft}/publish', [WizardDraftController::class, 'publish'])
        ->name('wizard-drafts.publish');
    Route::delete('wizard-drafts/{draft}', [WizardDraftController::class, 'destroy'])
        ->name('wizard-drafts.destroy');
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
