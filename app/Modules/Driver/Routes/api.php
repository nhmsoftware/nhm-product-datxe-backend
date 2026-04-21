<?php

namespace App\Modules\Driver\Routes;

use App\Modules\Driver\Http\Controllers\DriverController;
use App\Modules\Driver\Http\Controllers\DriverOperationController;
use App\Modules\Driver\Http\Controllers\AdminDriverController;
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

        Route::post('ride/{rideId}/cancel', [DriverOperationController::class, 'cancelOrder'])
            ->name('driver.ride.cancel');

        // UC-36: Thông báo đã đến / Xác nhận đón khách
        Route::post('ride/{rideId}/arrived', [DriverOperationController::class, 'notifyArrived'])
            ->name('driver.ride.arrived');

        Route::post('ride/{rideId}/pickup', [DriverOperationController::class, 'pickupRide'])
            ->name('driver.ride.pickup');

        // UC-35: Bắt đầu thực hiện chuyến đi
        Route::post('ride/{rideId}/start', [DriverOperationController::class, 'startRide'])
            ->name('driver.ride.start');

        // UC-40: Hoàn thành chuyến đi
        Route::post('ride/{rideId}/complete', [DriverOperationController::class, 'completeRide'])
            ->name('driver.ride.complete');

        // UC-28: Phản hồi yêu cầu hủy chuyến xe
        Route::post('ride/{rideId}/cancel-respond', [DriverOperationController::class, 'respondToCancellation'])
            ->name('driver.ride.cancel_respond');
    });

// Admin Routes
Route::prefix('v1/admin/driver')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('applications', [AdminDriverController::class, 'index'])
            ->name('admin.driver.applications.index');

        Route::get('applications/{id}', [AdminDriverController::class, 'show'])
            ->name('admin.driver.applications.show');

        Route::post('applications/{id}/approve', [AdminDriverController::class, 'approve'])
            ->name('admin.driver.application.approve');

        Route::get('groups', [AdminDriverController::class, 'groups'])
            ->name('admin.driver.groups.index');
    });
