<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

require __DIR__.'/admin.php';
require __DIR__.'/products.php';
require __DIR__.'/checkout.php';
require __DIR__.'/settings.php';
