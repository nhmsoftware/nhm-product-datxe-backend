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
    // UC-04: View Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('user.profile.show');

    // UC-05: Edit Profile
    Route::prefix('profile')->group(function () {
        // Lấy thông tin để edit (trả về form data)
        Route::get('/edit', [EditProfileController::class, 'edit'])->name('user.profile.edit');

        // Cập nhật thông tin cơ bản
        Route::put('/', [EditProfileController::class, 'update'])->name('user.profile.update');
        Route::patch('/', [EditProfileController::class, 'update'])->name('user.profile.patch');

        // Xác thực OTP cho thông tin nhạy cảm
        Route::post('/verify-otp', [EditProfileController::class, 'verifyOtp'])->name('user.profile.verify-otp');

        // Đổi mật khẩu
        Route::post('/change-password', [EditProfileController::class, 'changePassword'])->name('user.profile.change-password');
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
