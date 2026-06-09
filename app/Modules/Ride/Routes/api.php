<?php

namespace App\Modules\Ride\Routes;

use App\Modules\Ride\Http\Controllers\RideCommunicationController;
use App\Modules\Ride\Http\Controllers\RideController;
use Illuminate\Support\Facades\Route;

/**
 * Các route dành cho Khách hàng (Customer) - Prefix: v1/ride
 */
Route::prefix('v1/ride')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    // UC-09: Lấy danh sách loại xe kèm giá ước tính trước khi confirm
    Route::post('vehicle-options', [RideController::class, 'estimateRideOptions'])->name('ride.vehicle-options');

    // UC-12: Xác nhận đặt xe
    Route::post('confirm', [RideController::class, 'confirmBooking'])->name('ride.confirm');

    // UC-26: Đặt xe đi tỉnh
    Route::post('intercity', [RideController::class, 'createIntercity'])->name('ride.intercity');

    // UC-27: Đặt xe sân bay
    Route::get('airports', [RideController::class, 'listAirports'])->name('ride.airports');
    Route::post('airport', [RideController::class, 'createAirport'])->name('ride.airport');

    // UC-25: Tạo đơn giao hàng
    Route::post('delivery', [RideController::class, 'createDelivery'])->name('ride.delivery');

    // UC-13: Theo dõi tài xế realtime
    Route::get('{rideId}/tracking', [RideController::class, 'showTracking'])->name('ride.tracking.show');


    // UC-14: Chat / Call Driver (Customer side)
    Route::get('{rideId}/communication/messages', [RideCommunicationController::class, 'index'])->name('ride.communication.messages.index');
    Route::post('{rideId}/communication/messages', [RideCommunicationController::class, 'send'])->name('ride.communication.messages.send');
    Route::post('{rideId}/communication/calls', [RideCommunicationController::class, 'initiateCall'])->name('ride.communication.calls.initiate');
    Route::post('{rideId}/communication/calls/{callId}/status', [RideCommunicationController::class, 'updateCallStatus'])->name('ride.communication.calls.status');

    // UC-15: Hủy chuyến xe
    Route::post('{rideId}/cancel', [RideController::class, 'cancel'])->name('ride.cancel');

    // UC-28: Yêu cầu hủy chuyến xe
    Route::post('{rideId}/cancel-request', [RideController::class, 'requestCancellation'])->name('ride.cancel_request');
    Route::post('{rideId}/cancel-response', [RideController::class, 'respondToCancellation'])->name('ride.cancel_response');

    // UC-29: Xem chi tiết chuyến xe (Customer)
    Route::get('{rideId}', [RideController::class, 'show'])->name('ride.show');
});

/**
 * Các route dành cho Tài xế (Driver) - Prefix: v1/driver
 * Để đồng bộ với các API khác của tài xế
 */
Route::prefix('v1/driver')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    // UC-47: Danh sách chuyến xe đặt trước (Scheduled Rides)
    Route::get('scheduled-rides', [RideController::class, 'getAvailableScheduledRides'])->name('driver.scheduled.index');
    
    // UC-48: Chi tiết chuyến xe đặt trước
    Route::get('scheduled-rides/{rideId}', [RideController::class, 'getScheduledRideDetail'])->name('driver.scheduled.detail');
    
    // UC-49: Chấp nhận chuyến xe đặt trước
    Route::post('scheduled-rides/{rideId}/accept', [RideController::class, 'acceptScheduledRide'])->name('driver.scheduled.accept');
    
    // UC-50: Hủy chuyến xe đặt trước đã nhận
    Route::post('scheduled-rides/{rideId}/cancel', [RideController::class, 'driverCancelScheduledRide'])->name('driver.scheduled.cancel');
    
    // UC-51: Quản lý danh sách chuyến xe đã nhận
    Route::get('managed-rides', [RideController::class, 'getDriverManagedRides'])->name('driver.managed.index');

    // UC-51.1: Lấy danh sách chuyến xe (lịch sử/đang xử lý) của tài xế
    Route::get('rides', [RideController::class, 'getDriverRides'])->name('driver.rides.index');

    // UC-37: Chụp/tải ảnh xác nhận lấy hàng (Capture Pickup Proof)
    Route::post('ride/{rideId}/pickup-proof', [RideController::class, 'capturePickupProof'])->name('driver.ride.pickup_proof');

    // UC-38: Chụp/tải ảnh xác nhận giao hàng (Capture Delivery Proof)
    Route::post('ride/{rideId}/delivery-proof', [RideController::class, 'captureDeliveryProof'])->name('driver.ride.delivery_proof');
});

/**
 * Các route dành cho Quản trị viên (Admin) - Prefix: v1/admin/rides
 */
Route::prefix('v1/admin/rides')->middleware(['auth:sanctum'])->group(function () {
    // UC-122: Manage Scheduled Ride Bookings
    Route::prefix('scheduled')->group(function () {
        Route::post('/', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'store'])->name('admin.rides.scheduled.store');
        Route::get('/', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'index'])->name('admin.rides.scheduled.index');
        Route::get('/{id}', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'show'])->name('admin.rides.scheduled.show');
        Route::put('/{id}', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'update'])->name('admin.rides.scheduled.update');
        Route::delete('/{id}', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'destroy'])->name('admin.rides.scheduled.destroy');
        Route::post('/assign', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'assign'])->name('admin.rides.scheduled.assign');
        Route::post('/push-to-pool', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'pushToPool'])->name('admin.rides.scheduled.push_to_pool');
    });
});

/**
 * Các route dành cho Quản trị viên (Admin) - Dịch vụ Lái hộ
 */
Route::prefix('v1/admin/chauffeur')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/rides', [\App\Modules\Ride\Http\Controllers\AdminChauffeurRideController::class, 'index'])->name('admin.chauffeur.rides.index');
});

/**
 * Các route dành cho Quản trị viên (Admin) - Quản lý Dịch vụ (Giao hàng, Đồ ăn)
 */
Route::prefix('v1/admin/services')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/', [\App\Modules\Ride\Http\Controllers\AdminServiceOrderController::class, 'store'])->name('admin.services.store');
    Route::get('/', [\App\Modules\Ride\Http\Controllers\AdminServiceOrderController::class, 'index'])->name('admin.services.index');
    Route::get('/{id}', [\App\Modules\Ride\Http\Controllers\AdminServiceOrderController::class, 'show'])
        ->name('admin.services.show')
        ->where('id', '[0-9]+');
    Route::put('/{id}', [\App\Modules\Ride\Http\Controllers\AdminServiceOrderController::class, 'update'])->name('admin.services.update');
    Route::delete('/{id}', [\App\Modules\Ride\Http\Controllers\AdminServiceOrderController::class, 'destroy'])->name('admin.services.destroy');
});
