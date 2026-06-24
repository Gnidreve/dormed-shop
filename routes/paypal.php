<?php

use App\Http\Controllers\PayPalController;
use Illuminate\Support\Facades\Route;

Route::prefix('paypal')->name('paypal.')->group(function (): void {
    // REST API endpoints (called from frontend JS SDK)
    Route::post('/order/create', [PayPalController::class, 'createOrder'])
        ->middleware('auth')
        ->name('order.create');

    Route::post('/order/capture', [PayPalController::class, 'captureOrder'])
        ->middleware('auth')
        ->name('order.capture');

    // Return URL after PayPal approval
    Route::get('/after-payment', [PayPalController::class, 'afterPayment'])
        ->name('after-payment');

    // Webhook (no auth — verified via signature)
    Route::post('/webhook', [PayPalController::class, 'webhook'])
        ->middleware('throttle:60,1')
        ->name('webhook');
});
