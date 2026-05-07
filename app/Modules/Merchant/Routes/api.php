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

        // Menu Management (UC-57, UC-58, UC-59)
        Route::get('/menu', [\App\Modules\Merchant\Http\Controllers\MerchantMenuController::class, 'index'])->name('merchant.menu.index');
        Route::post('/menu/items', [\App\Modules\Merchant\Http\Controllers\MerchantMenuController::class, 'store'])->name('merchant.menu.items.store');
        Route::post('/menu/items/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantMenuController::class, 'update'])->name('merchant.menu.items.update');
        Route::delete('/menu/items/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantMenuController::class, 'delete'])->name('merchant.menu.items.delete');

        // Store Management (UC-53, UC-46, UC-45, etc.)
        Route::get('/store', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getInfo'])->name('merchant.store.info');
        Route::put('/store/status', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateStatus'])->name('merchant.store.update_status');
        Route::put('/store/hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateHours'])->name('merchant.store.update_hours');
        Route::put('/store/weekly-hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateWeeklyHours'])->name('merchant.store.update_weekly_hours');
        Route::get('/store/commission-packages', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getPackages'])->name('merchant.store.commission_packages');
        Route::put('/store/commission-package', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updatePackage'])->name('merchant.store.update_commission_package');
        Route::put('/store/discount', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateDiscount'])->name('merchant.store.update_discount');

        // Combo Management (UC-61, UC-54, UC-55, UC-56, UC-62)
        Route::get('/combos', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'index'])->name('merchant.combos.index');
        Route::get('/combos/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'show'])->name('merchant.combos.show');
        Route::post('/combos', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'store'])->name('merchant.combos.store');
        Route::put('/combos/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'update'])->name('merchant.combos.update');
        Route::delete('/combos/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'destroy'])->name('merchant.combos.destroy');
        Route::patch('/combos/{id}/status', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'updateStatus'])->name('merchant.combos.update_status');
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
