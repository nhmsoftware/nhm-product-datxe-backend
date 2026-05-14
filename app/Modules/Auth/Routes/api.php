<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->as('auth.')->group(function () {

    // Public routes (Guest access)
    Route::post('authenticate-otp', [AuthController::class, 'authenticateOtp'])->name('authenticate-otp'); // UC-01, UC-02, UC-03
    Route::post('register',         [AuthController::class, 'register'])->name('register');                 // UC-01
    Route::post('login',            [AuthController::class, 'login'])->name('login');                       // UC-02
    Route::post('google-login',     [AuthController::class, 'googleLogin'])->name('google-login');           // UC-02
    Route::post('apple-login',      [AuthController::class, 'appleLogin'])->name('apple-login');            // UC-02
    Route::post('forgot-password',  [AuthController::class, 'forgotPassword'])->name('forgot-password');    // UC-03


    // Protected routes
    Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });

});
