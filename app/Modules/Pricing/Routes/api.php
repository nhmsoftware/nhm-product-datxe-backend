<?php

declare(strict_types=1);

use App\Modules\Pricing\Http\Controllers\AdminPricingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pricing Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/admin/pricing')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // UC-91: Configure Pricing
        Route::get('configs', [AdminPricingController::class, 'getConfigs'])
            ->name('admin.pricing.configs');

        Route::post('/configs', [AdminPricingController::class, 'updateConfig']);
        Route::post('/configs/{vehicleTypeId}/archive', [AdminPricingController::class, 'archiveConfig'])
            ->name('admin.pricing.configs.archive');
        Route::delete('/configs/{vehicleType}/reset', [AdminPricingController::class, 'resetToDefault'])
            ->name('admin.pricing.configs.reset');

        Route::get('history/{vehicleType}', [AdminPricingController::class, 'getHistory'])
            ->name('admin.pricing.history');

        Route::post('toggle-free-mode', [AdminPricingController::class, 'toggleFreeMode'])
            ->name('admin.pricing.toggle_free_mode');

        // UC-96: Set Surge Pricing
        Route::get('surge-rules', [AdminPricingController::class, 'listSurgeRules'])
            ->name('admin.pricing.surge_rules.list');

        Route::post('surge-rules', [AdminPricingController::class, 'saveSurgeRule'])
            ->name('admin.pricing.surge_rules.save');

        Route::delete('surge-rules/{ruleId}', [AdminPricingController::class, 'deleteSurgeRule'])
            ->name('admin.pricing.surge_rules.delete');
        // UC-121: Configure Scheduled Ride Pricing
        Route::prefix('scheduled')->group(function () {
            Route::get('/', [\App\Modules\Pricing\Http\Controllers\AdminScheduledPricingController::class, 'show'])
                ->name('admin.pricing.scheduled.show');
            Route::post('/', [\App\Modules\Pricing\Http\Controllers\AdminScheduledPricingController::class, 'update'])
                ->name('admin.pricing.scheduled.update');

            // UC-122: Bật/Tắt chế độ phân phối thủ công
            // POST { "mode": 1 } → BẬT Admin kiểm soát (tài xế không thấy chuyến)
            // POST { "mode": 2 } → TẮT Admin / Tự động đẩy cho tài xế
            Route::post('toggle-dispatch', [\App\Modules\Pricing\Http\Controllers\AdminScheduledPricingController::class, 'toggleDispatch'])
                ->name('admin.pricing.scheduled.toggle_dispatch');
            
            Route::post('toggle-internal-auto-push', [\App\Modules\Pricing\Http\Controllers\AdminScheduledPricingController::class, 'toggleInternalAutoPush'])
                ->name('admin.pricing.scheduled.toggle_internal_auto_push');
        });
    });
