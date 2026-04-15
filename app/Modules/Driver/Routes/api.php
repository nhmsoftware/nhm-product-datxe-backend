<?php

namespace App\Modules\Driver\Routes;

use App\Modules\Driver\Http\Controllers\DriverController;
use App\Modules\Driver\Http\Controllers\DriverOperationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Module — API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/driver')
    ->middleware(['auth:sanctum', 'check.account.status'])
    ->group(function () {

        // UC-30 — Nộp hồ sơ đăng ký tài xế (Thông tin + KYC)
        Route::post('register/submit', [DriverController::class, 'submit'])
            ->name('register.submit');

        // UC-31 — Cập nhật trạng thái Go Online / Go Offline
        Route::put('status', [DriverOperationController::class, 'toggleStatus'])
            ->name('status.toggle');
    });
