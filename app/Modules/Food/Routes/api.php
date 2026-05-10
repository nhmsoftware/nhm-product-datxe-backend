<?php

declare(strict_types=1);

use App\Modules\Food\Http\Controllers\FoodOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/food')->middleware(['auth:sanctum'])->group(function () {
    // UC-18: Order Food
    Route::post('/order', [FoodOrderController::class, 'create'])->name('food.order.create');
    Route::post('/order/estimate', [FoodOrderController::class, 'estimate'])->name('food.order.estimate');
    
    // UC-20: Rate Food
    Route::post('/order/{orderId}/rate', [\App\Modules\Food\Http\Controllers\FoodRatingController::class, 'rate'])->name('food.order.rate');
});
