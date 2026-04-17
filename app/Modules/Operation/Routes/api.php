<?php

use App\Modules\Operation\Http\Controllers\OperationController;
use Illuminate\Support\Facades\Route;

/**
 * Routes cho module Operation (Quản lý vận hành).
 */
Route::prefix('v1/operation')
    ->middleware(['auth:sanctum'])
    ->group(function () {

    // UC-35: Cập nhật vị trí hiện tại
    Route::post('location', [OperationController::class, 'updateLocation'])
        ->name('location.update');

    // UC-34: Xem chỉ đường
    Route::get('navigation/{rideId}', [OperationController::class, 'getNavigation'])
        ->name('navigation.get');

});
