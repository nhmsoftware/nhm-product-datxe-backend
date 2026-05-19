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
        Route::patch('/menu/items/{id}/status', [\App\Modules\Merchant\Http\Controllers\MerchantMenuController::class, 'updateStatus'])->name('merchant.menu.items.update_status');
        Route::delete('/menu/items/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantMenuController::class, 'delete'])->name('merchant.menu.items.delete');

        // Store Management (UC-53, UC-46, UC-45, etc.)
        Route::get('/store', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getInfo'])->name('merchant.store.info');
        Route::put('/store/status', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateStatus'])->name('merchant.store.update_status');
        Route::put('/store/hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateHours'])->name('merchant.store.update_hours');
        Route::put('/store/weekly-hours', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateWeeklyHours'])->name('merchant.store.update_weekly_hours');
        Route::get('/store/commission-packages', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getPackages'])->name('merchant.store.commission_packages');
        Route::put('/store/commission-package', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updatePackage'])->name('merchant.store.update_commission_package');
        Route::put('/store/discount', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'updateDiscount'])->name('merchant.store.update_discount');
        Route::get('/store/stats/orders', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getOrderStats'])->name('merchant.store.stats.orders');
        Route::get('/store/stats/revenue', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getRevenueStats'])->name('merchant.store.stats.revenue');
        Route::get('/store/stats/average-order-value', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getAverageOrderValue'])->name('merchant.store.stats.average_order_value');
        Route::get('/store/stats/revenue-chart', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getRevenueChart'])->name('merchant.store.stats.revenue_chart');
        Route::get('/store/stats/recent-transactions', [\App\Modules\Merchant\Http\Controllers\MerchantStoreController::class, 'getRecentTransactions'])->name('merchant.store.stats.recent_transactions');

        // Combo Management (UC-61, UC-54, UC-55, UC-56, UC-62)
        Route::get('/combos', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'index'])->name('merchant.combos.index');
        Route::get('/combos/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'show'])->name('merchant.combos.show');
        Route::post('/combos', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'store'])->name('merchant.combos.store');
        Route::put('/combos/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'update'])->name('merchant.combos.update');
        Route::delete('/combos/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'destroy'])->name('merchant.combos.destroy');
        Route::patch('/combos/{id}/status', [\App\Modules\Merchant\Http\Controllers\MerchantComboController::class, 'updateStatus'])->name('merchant.combos.update_status');

        // Order Management (UC-69, UC-70, UC-61 -> UC-67)
        Route::prefix('orders')->group(function () {
            Route::get('/{id}', [\App\Modules\Merchant\Http\Controllers\MerchantOrderController::class, 'show'])->name('merchant.orders.show');
            Route::post('/{id}/accept', [\App\Modules\Merchant\Http\Controllers\MerchantOrderController::class, 'accept'])->name('merchant.orders.accept'); // UC-71
            Route::post('/{id}/reject', [\App\Modules\Merchant\Http\Controllers\MerchantOrderController::class, 'reject'])->name('merchant.orders.reject'); // UC-72
            Route::post('/{id}/preparing', [\App\Modules\Merchant\Http\Controllers\MerchantOrderController::class, 'preparing'])->name('merchant.orders.preparing');
            Route::post('/{id}/ready', [\App\Modules\Merchant\Http\Controllers\MerchantOrderController::class, 'ready'])->name('merchant.orders.ready'); // UC-73
            Route::post('/{id}/cancel', [\App\Modules\Merchant\Http\Controllers\MerchantOrderController::class, 'cancel'])->name('merchant.orders.cancel'); // UC-75
            Route::post('/{id}/cancellation/handle', [\App\Modules\Merchant\Http\Controllers\MerchantOrderController::class, 'handleCancellation'])->name('merchant.orders.handle_cancellation'); // UC-74
        });
    });
});

// Customer Routes (Explore Nearby Merchants & Menus)
Route::prefix('v1/customer/merchants')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [\App\Modules\Merchant\Http\Controllers\CustomerMerchantController::class, 'index'])
            ->name('customer.merchants.index');
            
        Route::get('/{id}', [\App\Modules\Merchant\Http\Controllers\CustomerMerchantController::class, 'show'])
            ->name('customer.merchants.show');
            
        Route::get('/{id}/menu', [\App\Modules\Merchant\Http\Controllers\CustomerMerchantController::class, 'menu'])
            ->name('customer.merchants.menu');
    });

// Admin Routes
Route::prefix('v1/admin/merchant')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // UC-86 Manage Merchant
        Route::get('/', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'index'])
            ->name('admin.merchant.index');

        Route::get('/{id}', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'show'])
            ->name('admin.merchant.show');

        Route::post('/{id}/approve', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'approve'])
            ->name('admin.merchant.approve');

        Route::post('/{id}/reject', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'reject'])
            ->name('admin.merchant.reject');

        Route::post('/{id}/toggle-lock', [\App\Modules\Merchant\Http\Controllers\AdminMerchantController::class, 'toggleLock'])
            ->name('admin.merchant.toggle_lock');
    });
