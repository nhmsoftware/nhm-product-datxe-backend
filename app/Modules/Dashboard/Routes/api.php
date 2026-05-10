<?php

declare(strict_types=1);

use App\Modules\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/admin/dashboard')
    ->middleware(['auth:sanctum']) // Should probably add admin role check middleware here
    ->group(function () {
        // UC-76: View Dashboard
        Route::get('/', [DashboardController::class, 'getStats'])->name('admin.dashboard.stats');
        
        // Dashboard Reports
        Route::get('/revenue', [DashboardController::class, 'getRevenueReport'])->name('admin.dashboard.revenue');
        Route::get('/area', [DashboardController::class, 'getAreaReport'])->name('admin.dashboard.area');
        Route::get('/commission', [DashboardController::class, 'getCommissionReport'])->name('admin.dashboard.commission');
        Route::get('/orders', [DashboardController::class, 'getOrderReport'])->name('admin.dashboard.orders');
        Route::get('/detailed', [DashboardController::class, 'getDetailedReport'])->name('admin.dashboard.detailed');
        Route::get('/top-drivers', [DashboardController::class, 'getTopDriversReport'])->name('admin.dashboard.top-drivers');
    });
