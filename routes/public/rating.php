<?php

use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;

Route::post('/products/{product}/ratings', [RatingController::class, 'store'])->name('ratings.store');
