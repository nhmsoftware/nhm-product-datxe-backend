<?php

namespace App\Modules\Ride\Routes;

use App\Modules\Ride\Http\Controllers\RideCommunicationController;
use App\Modules\Ride\Http\Controllers\RideController;
use Illuminate\Support\Facades\Route;

/**
 * Các route dành cho Khách hàng (Customer) - Prefix: v1/ride
 */
Route::prefix('v1/ride')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    // [DEPRECATED] UC-08: Tạo bản nháp chuyến xe — Không còn expose ra client
    // Route::post('draft', [RideController::class, 'createDraft'])->name('ride.draft');

    // UC-09: Lấy danh sách loại xe kèm giá ước tính (Stateless — truyền tọa độ, không cần draft)
    Route::get('vehicles', [RideController::class, 'getVehicleOptions'])->name('ride.vehicles');

    // UC-10: Xem chi tiết giá ước tính (giữ nguyên, dùng sau khi đã có ride)
    Route::get('{rideId}/price', [RideController::class, 'getPriceEstimate'])->name('ride.price');

    // UC-11: Áp dụng / Xóa voucher
    Route::post('{rideId}/voucher', [RideController::class, 'applyVoucher'])->name('ride.voucher.apply');
    Route::delete('{rideId}/voucher', [RideController::class, 'removeVoucher'])->name('ride.voucher.remove');

    // UC-12: Xác nhận đặt xe (tạo draft + confirm trong 1 bước)
    Route::post('book', [RideController::class, 'confirmBooking'])->name('ride.book');

    // UC-26: Đặt xe đi tỉnh
    Route::post('intercity', [RideController::class, 'createIntercity'])->name('ride.intercity');

    // UC-27: Đặt xe sân bay
    Route::get('airports', [RideController::class, 'listAirports'])->name('ride.airports');
    Route::post('airport', [RideController::class, 'createAirport'])->name('ride.airport');

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
});

/**
 * Các route dành cho Quản trị viên (Admin) - Prefix: v1/admin/rides
 */
Route::prefix('v1/admin/rides')->middleware(['auth:sanctum'])->group(function () {
    // UC-122: Manage Scheduled Ride Bookings
    Route::prefix('scheduled')->group(function () {
        Route::get('/', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'index'])->name('admin.rides.scheduled.index');
        Route::get('/{id}', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'show'])->name('admin.rides.scheduled.show');
        Route::post('/assign', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'assign'])->name('admin.rides.scheduled.assign');
        Route::post('/push-to-pool', [\App\Modules\Ride\Http\Controllers\AdminScheduledRideController::class, 'pushToPool'])->name('admin.rides.scheduled.push_to_pool');
    });
});
