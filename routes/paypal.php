<?php

use App\Http\Controllers\PayPalController;
use Illuminate\Support\Facades\Route;

Route::prefix('paypal')->name('paypal.')->group(function (): void {
    // REST API endpoints (called from frontend JS SDK).
    // "verified": Bestellungen erst nach bestätigter E-Mail-Adresse.
    // "throttle" (S-2): jeder Klick erzeugt PayPal-Order + DB-Order; begrenzt
    // die Menge, die ein eingeloggter Nutzer/Bot anlegen kann.
    Route::post('/order/create', [PayPalController::class, 'createOrder'])
        ->middleware(['auth', 'verified', 'throttle:10,1'])
        ->name('order.create');

    Route::post('/order/capture', [PayPalController::class, 'captureOrder'])
        ->middleware(['auth', 'verified'])
        ->name('order.capture');

    // Return URL after PayPal approval. "auth" (S-3): PayPal leitet den
    // eingeloggten Käufer zurück, verhindert Capture-Trigger durch Dritte
    // mit bekanntem Token (die Success-Seite prüft zusätzlich Ownership).
    Route::get('/after-payment', [PayPalController::class, 'afterPayment'])
        ->middleware('auth')
        ->name('after-payment');

    // Webhook (no auth — verified via signature)
    Route::post('/webhook', [PayPalController::class, 'webhook'])
        ->middleware('throttle:60,1')
        ->name('webhook');
});
