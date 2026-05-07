<?php

declare(strict_types=1);

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

Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {

    // UC-04: View Profile
    // URL: GET /api/v1/user/profile
    Route::get('v1/user/profile', [ProfileController::class, 'show'])->name('user.profile.show');

    // UC-05: Edit Profile actions
    // Cập nhật thông tin (PUT/PATCH)
    Route::match(['put', 'patch'], 'v1/user/profile', [ProfileController::class, 'update'])->name('user.profile.update');

    // Các thao tác bổ trợ hồ sơ
    Route::post('v1/user/profile/verify-otp', [ProfileController::class, 'verifyOtp'])->name('user.profile.verify-otp');
    Route::post('v1/user/profile/change-password', [ProfileController::class, 'changePassword'])->name('user.profile.change-password');

    // UC-06: Saved Addresses (Địa chỉ đã lưu)
    // URL cơ sở: /api/v1/user/addresses
    Route::get('v1/user/addresses', [SavedAddressController::class, 'index'])->name('user.addresses.index');
    Route::post('v1/user/addresses', [SavedAddressController::class, 'store'])->name('user.addresses.store');
    Route::get('v1/user/addresses/{id}', [SavedAddressController::class, 'show'])->name('user.addresses.show');
    Route::match(['put', 'patch'], 'v1/user/addresses/{id}', [SavedAddressController::class, 'update'])->name('user.addresses.update');
    Route::delete('v1/user/addresses/{id}', [SavedAddressController::class, 'destroy'])->name('user.addresses.destroy');

    // Đặt làm địa chỉ mặc định
    Route::post('v1/user/addresses/{id}/default', [SavedAddressController::class, 'setDefault'])->name('user.addresses.set-default');
});

// Admin Routes (UC-77/UC-80)
Route::prefix('v1/admin')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Quản lý khách hàng (Users)
        Route::prefix('users')->group(function () {
            Route::get('customers', [\App\Modules\User\Http\Controllers\AdminUserController::class, 'listCustomers'])
                ->name('admin.users.customers.index');

            Route::get('{userId}', [\App\Modules\User\Http\Controllers\AdminUserController::class, 'show'])
                ->name('admin.users.show');

            Route::put('{userId}/status', [\App\Modules\User\Http\Controllers\AdminUserController::class, 'updateStatus'])
                ->name('admin.users.status.update');
        });

        // Quản lý tài xế (Drivers)
        Route::prefix('drivers')->group(function () {
            Route::get('/', [\App\Modules\User\Http\Controllers\AdminDriverController::class, 'listDrivers'])
                ->name('admin.drivers.index');
            
            Route::get('export', [\App\Modules\User\Http\Controllers\AdminDriverController::class, 'export'])
                ->name('admin.drivers.export');

            Route::get('{userId}', [\App\Modules\User\Http\Controllers\AdminDriverController::class, 'show'])
                ->name('admin.drivers.show');


            Route::put('{userId}/status', [\App\Modules\User\Http\Controllers\AdminDriverController::class, 'updateStatus'])
                ->name('admin.drivers.status.update');
            
            Route::post('{userId}/assign-group', [\App\Modules\User\Http\Controllers\AdminDriverController::class, 'assignGroup'])
                ->name('admin.drivers.assign-group');
            
            Route::post('{userId}/approve', [\App\Modules\User\Http\Controllers\AdminDriverController::class, 'approve'])
                ->name('admin.drivers.approve');

            Route::post('{userId}/reject', [\App\Modules\User\Http\Controllers\AdminDriverController::class, 'reject'])
                ->name('admin.drivers.reject');
        });
    });
