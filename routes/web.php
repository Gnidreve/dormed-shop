<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/versand', 'VersandUndZahlung')->name('versand');
Route::inertia('/impressum', 'Impressum')->name('impressum');
Route::inertia('/agb', 'AGB')->name('agb');
Route::inertia('/datenschutz', 'Datenschutz')->name('datenschutz');
Route::inertia('/zahlung', 'Zahlung')->name('zahlung');
Route::inertia('/widerrufsbelehrung', 'Widerrufsbelehrung')->name('widerrufsbelehrung');
Route::inertia('/kontakt', 'Kontakt')->name('kontakt');

require __DIR__.'/admin.php';
require __DIR__.'/products.php';
require __DIR__.'/public/rating.php';
require __DIR__.'/checkout.php';
require __DIR__.'/settings.php';
require __DIR__.'/categories.php';
require __DIR__.'/paypal.php';
