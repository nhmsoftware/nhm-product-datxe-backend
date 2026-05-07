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
        Route::delete('/configs/{vehicleType}/reset', [AdminPricingController::class, 'resetToDefault'])
            ->name('admin.pricing.configs.reset');

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
        });
    });
