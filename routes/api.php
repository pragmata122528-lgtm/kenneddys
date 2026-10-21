<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

Route::middleware('auth:api')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    Route::middleware('role:customer,admin')->group(function (): void {
        Route::post('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
        Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
    });

    Route::prefix('vendor')->middleware('role:vendor')->group(function (): void {
        Route::get('/profile', [VendorController::class, 'profile'])->name('vendor.profile');
        Route::put('/profile', [VendorController::class, 'updateProfile'])->name('vendor.profile.update');
        Route::get('/products', [VendorController::class, 'products'])->name('vendor.products');
        Route::get('/orders', [VendorController::class, 'orders'])->name('vendor.orders');
        Route::get('/earnings', [VendorController::class, 'earnings'])->name('vendor.earnings');

        Route::middleware('vendor.approved')->group(function (): void {
            Route::post('/products', [ProductController::class, 'store'])->name('vendor.products.store');
            Route::put('/products/{product}', [ProductController::class, 'update'])->name('vendor.products.update');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('vendor.products.destroy');
        });
    });

    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::get('/vendors', [AdminController::class, 'vendors'])->name('admin.vendors.index');
        Route::patch('/vendors/{vendor}/approve', [AdminController::class, 'approveVendor'])->name('admin.vendors.approve');
        Route::patch('/vendors/{vendor}/reject', [AdminController::class, 'rejectVendor'])->name('admin.vendors.reject');
        Route::get('/earnings', [AdminController::class, 'earningsLedger'])->name('admin.earnings.ledger');
        Route::get('/stats', [AdminController::class, 'platformStats'])->name('admin.stats');
    });
});
