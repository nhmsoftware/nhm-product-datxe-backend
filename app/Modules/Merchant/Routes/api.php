<?php

declare(strict_types=1);

use App\Modules\Merchant\Http\Controllers\AdminMerchantController;
use App\Modules\Merchant\Http\Controllers\AdminMerchantMenuController;
use App\Modules\Merchant\Http\Controllers\CustomerMerchantController;
use App\Modules\Merchant\Http\Controllers\MerchantComboController;
use App\Modules\Merchant\Http\Controllers\MerchantMenuController;
use App\Modules\Merchant\Http\Controllers\MerchantOrderController;
use App\Modules\Merchant\Http\Controllers\MerchantRegistrationController;
use App\Modules\Merchant\Http\Controllers\MerchantStoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Merchant Module Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/merchant')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        // UC-52 Register Merchant — Business type options
        Route::get('/business-types', [MerchantRegistrationController::class, 'businessTypes'])->name('merchant.business_types');

        // UC-52 Register Merchant
        Route::post('/register', [MerchantRegistrationController::class, 'register'])->name('merchant.register');

        // Menu Management (UC-57, UC-58, UC-59)
        Route::get('/menu', [MerchantMenuController::class, 'index'])->name('merchant.menu.index');
        Route::get('/menu/categories', [MerchantMenuController::class, 'categories'])->name('merchant.menu.categories');
        Route::post('/menu/items', [MerchantMenuController::class, 'store'])->name('merchant.menu.items.store');
        Route::post('/menu/items/{id}', [MerchantMenuController::class, 'update'])->name('merchant.menu.items.update');
        Route::patch('/menu/items/{id}/status', [MerchantMenuController::class, 'updateStatus'])->name('merchant.menu.items.update_status');
        Route::delete('/menu/items/{id}', [MerchantMenuController::class, 'delete'])->name('merchant.menu.items.delete');

        // Store Management (UC-53, UC-46, UC-45, etc.)
        Route::get('/store', [MerchantStoreController::class, 'getInfo'])->name('merchant.store.info');
        Route::put('/store/status', [MerchantStoreController::class, 'updateStatus'])->name('merchant.store.update_status');
        Route::put('/store/hours', [MerchantStoreController::class, 'updateHours'])->name('merchant.store.update_hours');
        Route::put('/store/weekly-hours', [MerchantStoreController::class, 'updateWeeklyHours'])->name('merchant.store.update_weekly_hours');
        Route::get('/store/commission-packages', [MerchantStoreController::class, 'getPackages'])->name('merchant.store.commission_packages');
        Route::put('/store/commission-package', [MerchantStoreController::class, 'updatePackage'])->name('merchant.store.update_commission_package');
        Route::put('/store/discount', [MerchantStoreController::class, 'updateDiscount'])->name('merchant.store.update_discount');
        Route::get('/store/stats/orders', [MerchantStoreController::class, 'getOrderStats'])->name('merchant.store.stats.orders');
        Route::get('/store/stats/revenue', [MerchantStoreController::class, 'getRevenueStats'])->name('merchant.store.stats.revenue');
        Route::get('/store/stats/average-order-value', [MerchantStoreController::class, 'getAverageOrderValue'])->name('merchant.store.stats.average_order_value');
        Route::get('/store/stats/revenue-chart', [MerchantStoreController::class, 'getRevenueChart'])->name('merchant.store.stats.revenue_chart');
        Route::get('/store/stats/recent-transactions', [MerchantStoreController::class, 'getRecentTransactions'])->name('merchant.store.stats.recent_transactions');

        // Combo Management (UC-61, UC-54, UC-55, UC-56, UC-62)
        Route::get('/combos', [MerchantComboController::class, 'index'])->name('merchant.combos.index');
        Route::get('/combos/{id}', [MerchantComboController::class, 'show'])->name('merchant.combos.show');
        Route::post('/combos', [MerchantComboController::class, 'store'])->name('merchant.combos.store');
        Route::put('/combos/{id}', [MerchantComboController::class, 'update'])->name('merchant.combos.update');
        Route::delete('/combos/{id}', [MerchantComboController::class, 'destroy'])->name('merchant.combos.destroy');
        Route::patch('/combos/{id}/status', [MerchantComboController::class, 'updateStatus'])->name('merchant.combos.update_status');

        // Order Management (UC-69, UC-70, UC-61 -> UC-67)
        Route::prefix('orders')->group(function () {
            Route::get('/', [MerchantOrderController::class, 'index'])->name('merchant.orders.index'); // UC-69.1
            Route::get('/{id}', [MerchantOrderController::class, 'show'])->name('merchant.orders.show');
            Route::post('/{id}/accept', [MerchantOrderController::class, 'accept'])->name('merchant.orders.accept'); // UC-71
            Route::post('/{id}/reject', [MerchantOrderController::class, 'reject'])->name('merchant.orders.reject'); // UC-72
            Route::post('/{id}/preparing', [MerchantOrderController::class, 'preparing'])->name('merchant.orders.preparing');
            Route::post('/{id}/ready', [MerchantOrderController::class, 'ready'])->name('merchant.orders.ready'); // UC-73
            Route::post('/{id}/cancel', [MerchantOrderController::class, 'cancel'])->name('merchant.orders.cancel'); // UC-75
            Route::post('/{id}/cancellation/handle', [MerchantOrderController::class, 'handleCancellation'])->name('merchant.orders.handle_cancellation'); // UC-74
        });
    });
});

// Customer Routes (Explore Nearby Merchants & Menus)
Route::prefix('v1/customer/merchants')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [CustomerMerchantController::class, 'index'])
            ->name('customer.merchants.index');

        Route::get('/{id}', [CustomerMerchantController::class, 'show'])
            ->name('customer.merchants.show');

        Route::get('/{id}/menu', [CustomerMerchantController::class, 'menu'])
            ->name('customer.merchants.menu');
    });

// Public Admin Routes
Route::get('v1/admin/merchant/menu/export-template', [AdminMerchantMenuController::class, 'exportTemplate'])
    ->name('admin.merchant.menu.export_template');

// Admin Routes
Route::prefix('v1/admin/merchant')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // UC-86 Manage Merchant
        Route::get('/', [AdminMerchantController::class, 'index'])
            ->name('admin.merchant.index');

        Route::get('/{id}', [AdminMerchantController::class, 'show'])
            ->name('admin.merchant.show');

        Route::post('/{id}/approve', [AdminMerchantController::class, 'approve'])
            ->name('admin.merchant.approve');

        Route::post('/{id}/reject', [AdminMerchantController::class, 'reject'])
            ->name('admin.merchant.reject');

        Route::post('/{id}/toggle-lock', [AdminMerchantController::class, 'toggleLock'])
            ->name('admin.merchant.toggle_lock');

        // Admin Merchant Menu Operations
        Route::get('/{merchantId}/menu', [AdminMerchantMenuController::class, 'index'])
            ->name('admin.merchant.menu.index');
        Route::get('/{merchantId}/menu/categories', [AdminMerchantMenuController::class, 'categories'])
            ->name('admin.merchant.menu.categories');

        Route::post('/{merchantId}/menu/items', [AdminMerchantMenuController::class, 'store'])
            ->name('admin.merchant.menu.items.store');

        Route::match(['POST', 'PUT'], '/{merchantId}/menu/items/{itemId}', [AdminMerchantMenuController::class, 'update'])
            ->name('admin.merchant.menu.items.update');

        Route::delete('/{merchantId}/menu/items/{itemId}', [AdminMerchantMenuController::class, 'delete'])
            ->name('admin.merchant.menu.items.delete');

        Route::patch('/{merchantId}/menu/items/{itemId}/status', [AdminMerchantMenuController::class, 'updateStatus'])
            ->name('admin.merchant.menu.items.update_status');

        Route::get('/{merchantId}/menu/logs', [AdminMerchantMenuController::class, 'logs'])
            ->name('admin.merchant.menu.logs');

        Route::post('/{merchantId}/menu/import', [AdminMerchantMenuController::class, 'import'])
            ->name('admin.merchant.menu.import');
    });
