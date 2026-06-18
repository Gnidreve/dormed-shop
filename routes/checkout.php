<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/warenkorb', [CartController::class, 'index'])->name('cart.index');
Route::get('/checkout', [CartController::class, 'index'])->name('checkout.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{product}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{product}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::patch('/cart/shipping', [CartController::class, 'updateShipping'])->name('cart.shipping.update');
Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
Route::patch('/checkout/payment', [CheckoutController::class, 'updatePayment'])->name('checkout.payment.update');
Route::post('/checkout/submit', [CheckoutController::class, 'submit'])->middleware('auth')->name('checkout.submit');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/customer/orders', [CustomerOrderController::class, 'index'])->middleware('auth')->name('customer.orders');
Route::inertia('/checkout/error', 'Checkout/Error')->name('checkout.error');
