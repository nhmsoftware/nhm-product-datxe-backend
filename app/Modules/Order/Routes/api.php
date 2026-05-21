<?php

declare(strict_types=1);

use App\Modules\Order\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/customer')->middleware(['auth:sanctum'])->group(function () {
    // UC-19: View Order History
    Route::get('/orders', [OrderController::class, 'index'])->name('customer.orders.index');
    Route::get('/orders/{orderId}', [OrderController::class, 'show'])->name('customer.orders.show');
});

Route::prefix('v1/admin/services')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/orders', [\App\Modules\Order\Http\Controllers\AdminServiceManagementController::class, 'index'])->name('admin.services.orders.index');
    Route::post('/orders/assign', [\App\Modules\Order\Http\Controllers\AdminServiceManagementController::class, 'assign'])->name('admin.services.orders.assign');
    Route::post('/orders/push-to-pool', [\App\Modules\Order\Http\Controllers\AdminServiceManagementController::class, 'pushToPool'])->name('admin.services.orders.push_to_pool');
});

