<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
// Các route công khai
Route::post('v1/auth/authenticate-otp', [AuthController::class, 'authenticateOtp'])->name('auth.authenticate-otp');
Route::post('v1/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('v1/auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('v1/auth/google-login', [AuthController::class, 'googleLogin'])->name('auth.google-login');
Route::post('v1/auth/apple-login', [AuthController::class, 'appleLogin'])->name('auth.apple-login');
Route::post('v1/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');

// Các route cần đăng nhập (ví dụ dùng Sanctum)
Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    Route::post('v1/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
});
