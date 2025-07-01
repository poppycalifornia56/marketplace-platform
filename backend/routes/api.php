<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Api\Vendor\OrderController as VendorOrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Customer routes (some public, some protected)
Route::prefix('customer')->group(function () {
    Route::get('/products', [CustomerProductController::class, 'index']);
    Route::get('/products/{product}', [CustomerProductController::class, 'show']);
    Route::get('/categories', [CustomerProductController::class, 'categories']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/orders', [CustomerOrderController::class, 'store']);
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show']);
    });
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::apiResource('vendors', AdminVendorController::class);
        Route::apiResource('products', AdminProductController::class);
        Route::patch('/vendors/{vendor}/approve', [AdminVendorController::class, 'approve']);
        Route::patch('/vendors/{vendor}/reject', [AdminVendorController::class, 'reject']);
    });

    // Vendor routes
    Route::middleware('vendor')->prefix('vendor')->group(function () {
        Route::apiResource('products', VendorProductController::class);
        Route::get('/orders', [VendorOrderController::class, 'index']);
        Route::get('/orders/{order}', [VendorOrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [VendorOrderController::class, 'updateStatus']);
    });
});
