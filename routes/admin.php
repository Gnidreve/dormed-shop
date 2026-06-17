<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ManufacturerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SettingController;
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
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/manufacturers', [ManufacturerController::class, 'index'])->name('manufacturers.index');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
