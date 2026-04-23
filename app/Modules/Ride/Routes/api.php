<?php

namespace App\Modules\Ride\Routes;

use App\Modules\Ride\Http\Controllers\RideCommunicationController;
use App\Modules\Ride\Http\Controllers\RideController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/ride')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    // UC-29: Xem chi tiết chuyến xe
    Route::get('{rideId}', [RideController::class, 'show'])->name('ride.show');

    // UC-47: Danh sách chuyến xe đặt trước cho tài xế
    Route::get('driver/scheduled-rides', [RideController::class, 'getAvailableScheduledRides'])->name('ride.driver.scheduled');
    Route::get('driver/scheduled-rides/{rideId}', [RideController::class, 'getScheduledRideDetail'])->name('ride.driver.scheduled.detail');
    Route::post('driver/scheduled-rides/{rideId}/accept', [RideController::class, 'acceptScheduledRide'])->name('ride.driver.scheduled.accept');
    Route::post('driver/scheduled-rides/{rideId}/cancel', [RideController::class, 'driverCancelScheduledRide'])->name('ride.driver.scheduled.cancel');
    Route::get('driver/managed-rides', [RideController::class, 'getDriverManagedRides'])->name('ride.driver.managed');

    // UC-08: Tạo bản nháp chuyến xe
    Route::post('draft', [RideController::class, 'createDraft'])->name('ride.draft');

    // UC-09: Lấy danh sách loại xe kèm giá ước tính
    Route::get('{rideId}/vehicles', [RideController::class, 'getVehicleOptions'])->name('ride.vehicles');

    // UC-10: Xem chi tiết giá ước tính
    Route::get('{rideId}/price', [RideController::class, 'getPriceEstimate'])->name('ride.price');

    // UC-11: Áp dụng / Xóa voucher
    Route::post('{rideId}/voucher', [RideController::class, 'applyVoucher'])->name('ride.voucher.apply');
    Route::delete('{rideId}/voucher', [RideController::class, 'removeVoucher'])->name('ride.voucher.remove');

    // UC-12: Xác nhận đặt xe
    Route::post('{rideId}/confirm', [RideController::class, 'confirmBooking'])->name('ride.confirm');

    // UC-26: Đặt xe đi tỉnh
    Route::post('intercity', [RideController::class, 'createIntercity'])->name('ride.intercity');

    // UC-27: Đặt xe sân bay
    Route::post('airport', [RideController::class, 'createAirport'])->name('ride.airport');

    // UC-13: Theo dõi tài xế realtime
    Route::get('{rideId}/tracking', [RideController::class, 'showTracking'])->name('ride.tracking.show');
    Route::post('{rideId}/tracking/accept', [RideController::class, 'acceptTracking'])->name('ride.tracking.accept');
    Route::post('{rideId}/tracking/location', [RideController::class, 'updateDriverLocation'])->name('ride.tracking.location');
    Route::post('{rideId}/tracking/arrived', [RideController::class, 'markDriverArrived'])->name('ride.tracking.arrived');
    Route::post('{rideId}/tracking/driver-cancel', [RideController::class, 'cancelByDriver'])->name('ride.tracking.driver-cancel');

    // UC-14: Chat / Call Driver
    Route::get('{rideId}/communication/messages', [RideCommunicationController::class, 'index'])->name('ride.communication.messages.index');
    Route::post('{rideId}/communication/messages', [RideCommunicationController::class, 'send'])->name('ride.communication.messages.send');
    Route::post('{rideId}/communication/calls', [RideCommunicationController::class, 'initiateCall'])->name('ride.communication.calls.initiate');
    Route::post('{rideId}/communication/calls/{callId}/status', [RideCommunicationController::class, 'updateCallStatus'])->name('ride.communication.calls.status');

    // UC-15: Hủy chuyến xe
    Route::post('{rideId}/cancel', [RideController::class, 'cancel'])->name('ride.cancel');

    // UC-28: Yêu cầu hủy chuyến xe (cần xác nhận)
    Route::post('{rideId}/cancel-request', [RideController::class, 'requestCancellation'])->name('ride.cancel_request');
    Route::post('{rideId}/cancel-response', [RideController::class, 'respondToCancellation'])->name('ride.cancel_response');
});
