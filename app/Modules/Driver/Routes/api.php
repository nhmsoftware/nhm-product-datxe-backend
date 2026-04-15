<?php

namespace App\Modules\Driver\Routes;

use App\Modules\Driver\Http\Controllers\DriverController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver Module — API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/driver')
    ->middleware(['auth:sanctum', 'check.account.status'])
    ->group(function () {

        // UC-30 Bước 1 — Validate thông tin cá nhân + phương tiện → gửi OTP
        Route::post('register/send-otp', [DriverController::class, 'sendOtp'])
            ->name('register.send-otp');

        // UC-30 Bước 2 — Xác thực OTP + upload tài liệu → tạo hồ sơ Pending
        Route::post('register/submit', [DriverController::class, 'submit'])
            ->name('register.submit');
    });
