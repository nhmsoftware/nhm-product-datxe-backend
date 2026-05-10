<?php

declare(strict_types=1);

use App\Modules\Order\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/customer')->middleware(['auth:sanctum'])->group(function () {
    // UC-19: View Order History
    Route::get('/orders', [OrderController::class, 'index'])->name('customer.orders.index');
    Route::get('/orders/{orderId}', [OrderController::class, 'show'])->name('customer.orders.show');
});
