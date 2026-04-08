<?php

declare(strict_types=1);

use App\Modules\User\Http\Controllers\EditProfileController;
use App\Modules\User\Http\Controllers\ProfileController;
use App\Modules\User\Http\Controllers\SavedAddressController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {
    // UC-04 & UC-05: User Profile
    Route::prefix('profile')->group(function () {
        // Xem thông tin hồ sơ
        Route::get('/', [ProfileController::class, 'show'])->name('user.profile.show');

        // Cập nhật thông tin cơ bản
        Route::put('/', [ProfileController::class, 'update'])->name('user.profile.update');
        Route::patch('/', [ProfileController::class, 'update'])->name('user.profile.patch');

        // Xác thực OTP cho thông tin nhạy cảm
        Route::post('/verify-otp', [ProfileController::class, 'verifyOtp'])->name('user.profile.verify-otp');

        // Đổi mật khẩu
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('user.profile.change-password');
    });

    // UC-06: Saved Addresses
    Route::prefix('addresses')->group(function () {
        // Danh sách địa chỉ đã lưu
        Route::get('/', [SavedAddressController::class, 'index'])->name('user.addresses.index');

        // Tạo địa chỉ mới
        Route::post('/', [SavedAddressController::class, 'store'])->name('user.addresses.store');

        // Xem chi tiết địa chỉ
        Route::get('/{id}', [SavedAddressController::class, 'show'])->name('user.addresses.show');

        // Cập nhật địa chỉ
        Route::put('/{id}', [SavedAddressController::class, 'update'])->name('user.addresses.update');
        Route::patch('/{id}', [SavedAddressController::class, 'update'])->name('user.addresses.patch');

        // Xóa địa chỉ
        Route::delete('/{id}', [SavedAddressController::class, 'destroy'])->name('user.addresses.destroy');

        // Đặt làm địa chỉ mặc định
        Route::post('/{id}/default', [SavedAddressController::class, 'setDefault'])->name('user.addresses.set-default');
    });
});
