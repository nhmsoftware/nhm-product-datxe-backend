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
        Route::get('/', [DashboardController::class, 'getStats'])
            ->name('admin.dashboard.stats');
    });
