<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->as('auth.')->group(function () {

    // Public routes
    Route::post('authenticate-otp', [AuthController::class, 'authenticateOtp'])->name('authenticate-otp');
    Route::post('register',         [AuthController::class, 'register'])->name('register');
    Route::post('login',            [AuthController::class, 'login'])->name('login');
    Route::post('google-login',     [AuthController::class, 'googleLogin'])->name('google-login');
    Route::post('apple-login',      [AuthController::class, 'appleLogin'])->name('apple-login');
    Route::post('forgot-password',  [AuthController::class, 'forgotPassword'])->name('forgot-password');

    // Protected routes
    Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });

});
