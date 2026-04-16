<?php

namespace App\Modules\Driver\Routes;

use App\Modules\Driver\Http\Controllers\DriverController;
use App\Modules\Driver\Http\Controllers\DriverOperationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Module — API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/driver')
    ->middleware(['auth:sanctum', 'check.account.status'])
    ->group(function () {

        // UC-30 — Nộp hồ sơ đăng ký tài xế (Thông tin + KYC)
        Route::post('register/submit', [DriverController::class, 'submit'])
            ->name('register.submit');

        // UC-31: Cập nhật trạng thái Go Online / Go Offline
        Route::put('status', [DriverOperationController::class, 'toggleStatus'])
            ->name('driver.status.toggle');

        // UC-32: Chấp nhận đơn hàng/chuyến xe
        Route::post('ride/{rideId}/accept', [DriverOperationController::class, 'acceptOrder'])
            ->name('driver.ride.accept');

        // UC-33: Từ chối đơn hàng (Trước khi nhận)
        Route::post('ride/{rideId}/reject', [DriverOperationController::class, 'rejectOrder'])
            ->name('driver.ride.reject');

        // UC-33: Hủy chuyến đi (Sau khi đã nhận)
        Route::post('ride/{rideId}/cancel', [DriverOperationController::class, 'cancelOrder'])
            ->name('driver.ride.cancel');
    });
