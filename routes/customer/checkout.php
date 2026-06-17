<?php

use Illuminate\Support\Facades\Route;

Route::get('/checkout', fn () => inertia('Checkout/Index'))->name('checkout.index');
Route::get('/checkout/confirm', fn () => inertia('Checkout/Confirm'))->name('checkout.confirm');
