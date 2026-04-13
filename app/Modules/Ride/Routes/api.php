<?php

namespace App\Modules\Ride\Routes;

use App\Modules\Ride\Http\Controllers\RideController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/ride')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
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
});
