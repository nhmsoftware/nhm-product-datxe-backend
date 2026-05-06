<?php

declare(strict_types=1);

use App\Modules\RiskManagement\Http\Controllers\AdminAntiFraudController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Risk Management Module Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/admin/risk')->middleware(['auth:sanctum'])->group(function () {
    
    // UC-104: Anti-Fraud System
    Route::prefix('anti-fraud')->group(function () {
        Route::get('overview', [AdminAntiFraudController::class, 'overview'])->name('admin.risk.anti-fraud.overview');
        Route::get('alerts', [AdminAntiFraudController::class, 'listAlerts'])->name('admin.risk.anti-fraud.alerts');
        Route::get('alerts/{id}', [AdminAntiFraudController::class, 'show'])->name('admin.risk.anti-fraud.show');
    });
    // UC-105: Configure Penalty Rules
    Route::prefix('penalty-rules')->group(function () {
        Route::get('/', [\App\Modules\RiskManagement\Http\Controllers\AdminPenaltyRuleController::class, 'index'])->name('admin.risk.penalty-rules.index');
        Route::post('/', [\App\Modules\RiskManagement\Http\Controllers\AdminPenaltyRuleController::class, 'store'])->name('admin.risk.penalty-rules.store');
        Route::get('/{id}', [\App\Modules\RiskManagement\Http\Controllers\AdminPenaltyRuleController::class, 'show'])->name('admin.risk.penalty-rules.show');
        Route::put('/{id}', [\App\Modules\RiskManagement\Http\Controllers\AdminPenaltyRuleController::class, 'update'])->name('admin.risk.penalty-rules.update');
        Route::delete('/{id}', [\App\Modules\RiskManagement\Http\Controllers\AdminPenaltyRuleController::class, 'destroy'])->name('admin.risk.penalty-rules.destroy');
        Route::patch('/{id}/toggle-status', [\App\Modules\RiskManagement\Http\Controllers\AdminPenaltyRuleController::class, 'toggleStatus'])->name('admin.risk.penalty-rules.toggle-status');
    });

});
