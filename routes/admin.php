<?php

use App\Http\Controllers\Admin\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.store');
});

Route::post('/admin/logout', [LoginController::class, 'logout'])
    ->middleware('ensure.admin')
    ->name('admin.logout');

Route::middleware('ensure.admin')->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/', 'Admin/Dashboard')->name('dashboard');
});
