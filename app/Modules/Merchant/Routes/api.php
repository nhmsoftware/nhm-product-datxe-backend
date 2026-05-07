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

        // Store Management (UC-53, UC-46, UC-45, etc.)
        Route::get('/store', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getInfo'])->name('merchant.store.info');
        Route::put('/store/status', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateStatus'])->name('merchant.store.update_status');
        Route::put('/store/hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateHours'])->name('merchant.store.update_hours');
        Route::put('/store/weekly-hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateWeeklyHours'])->name('merchant.store.update_weekly_hours');
        Route::get('/store/commission-packages', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getPackages'])->name('merchant.store.commission_packages');
        Route::put('/store/commission-package', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updatePackage'])->name('merchant.store.update_commission_package');
        Route::put('/store/discount', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateDiscount'])->name('merchant.store.update_discount');
    });
});

// Admin Routes
Route::prefix('v1/admin/merchant')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('applications', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'index'])
            ->name('admin.merchant.applications.index');

        Route::get('applications/{id}', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'show'])
            ->name('admin.merchant.applications.show');

        Route::post('applications/{id}/approve', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'approve'])
            ->name('admin.merchant.application.approve');

        Route::post('applications/{id}/reject', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'reject'])
            ->name('admin.merchant.application.reject');
    });
