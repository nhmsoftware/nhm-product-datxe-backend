<?php

declare(strict_types=1);

use App\Modules\Merchant\Http\Controllers\MerchantRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Merchant Module Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/merchant')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        // UC-52 Register Merchant
        Route::post('/register', [MerchantRegistrationController::class, 'register'])->name('merchant.register');
        Route::post('/send-otp', [MerchantRegistrationController::class, 'sendOtp'])->name('merchant.send_otp');
        Route::post('/verify-otp', [MerchantRegistrationController::class, 'verifyOtp'])->name('merchant.verify_otp');

        // UC-53 Manage Store
        Route::get('/store', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getInfo'])->name('merchant.store.info');
        Route::put('/store/status', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateStatus'])->name('merchant.store.update_status');
        Route::put('/store/hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateHours'])->name('merchant.store.update_hours');
        Route::put('/store/weekly-hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateWeeklyHours'])->name('merchant.store.update_weekly_hours');
        Route::get('/store/commission-packages', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getPackages'])->name('merchant.store.commission_packages');
        Route::put('/store/commission-package', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updatePackage'])->name('merchant.store.update_commission_package');
        Route::put('/store/discount', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateDiscount'])->name('merchant.store.update_discount');
    });
});
