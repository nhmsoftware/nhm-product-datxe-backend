<?php

namespace App\Modules\Driver\Routes;

use App\Modules\Driver\Http\Controllers\DriverController;
use App\Modules\Driver\Http\Controllers\DriverOperationController;
use App\Modules\Driver\Http\Controllers\AdminDriverController;
use App\Modules\Driver\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Module — API Routes
|--------------------------------------------------------------------------
*/

// ── Public Routes ────────────────────────────────────────────────────────
Route::prefix('v1/driver')->group(function () {
    // UC-30 — Lấy danh sách dịch vụ tài xế có thể đăng ký
    Route::get('register/services', [DriverController::class, 'getRegistrationServices'])
        ->name('register.services');

    // Route phục vụ ảnh KYC từ storage local (Proxy)
    Route::get('files/{id}', [FileController::class, 'show'])
        ->name('driver.files.show');
});

// ── Protected Routes (Auth Required) ─────────────────────────────────────
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

        // UC-41: Xem tóm tắt thu nhập và xác nhận sẵn sàng
        Route::get('ride/{rideId}/summary', [DriverOperationController::class, 'getTripSummary'])
            ->name('driver.ride.summary');

        Route::post('ride/{rideId}/confirm-ready', [DriverOperationController::class, 'confirmReady'])
            ->name('driver.ride.confirm_ready');
    });

// ── Admin Routes ─────────────────────────────────────────────────────────
Route::prefix('v1/admin/driver')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('applications', [AdminDriverController::class, 'index'])
            ->name('admin.driver.applications.index');

        Route::get('applications/{id}', [AdminDriverController::class, 'show'])
            ->name('admin.driver.applications.show');

        Route::post('applications/{id}/approve', [AdminDriverController::class, 'approve'])
            ->name('admin.driver.application.approve');

        Route::post('users/{userId}/register-submit', [AdminDriverController::class, 'submitForUser'])
            ->name('admin.driver.user.register-submit');

        Route::get('groups', [AdminDriverController::class, 'groups'])
            ->name('admin.driver.groups.index');
    });
