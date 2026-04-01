<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Presentation\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Routes — Module User
|--------------------------------------------------------------------------
| Prefix  : /api/auth
| Middleware: throttle xem config/app.php
*/

Route::prefix('api/auth')->group(function () {

    // ── Public routes ─────────────────────────────────────────
    Route::post('send-otp', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:10,1')   // 10 req / 1 phút
        ->name('auth.send-otp');

    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])
        ->name('auth.verify-otp');

    Route::post('register', [AuthController::class, 'register'])
        ->name('auth.register');

    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')    // 5 lần / 1 phút chống brute-force
        ->name('auth.login');

    // ── Protected routes ──────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])
            ->name('auth.me');
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('auth.logout');
    });
});
