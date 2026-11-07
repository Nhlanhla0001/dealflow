<?php

use App\Http\Controllers\AgreementApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayPalWebhookController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

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
