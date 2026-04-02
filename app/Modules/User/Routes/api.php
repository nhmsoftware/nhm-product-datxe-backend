<?php

declare(strict_types=1);

use App\Modules\User\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
// Các route công khai
Route::post('v1/auth/authenticate-otp', [AuthController::class, 'authenticateOtp'])->name('auth.authenticate-otp');
Route::post('v1/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('v1/auth/login', [AuthController::class, 'login'])->name('auth.login');

// Các route cần đăng nhập (ví dụ dùng Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('v1/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('v1/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
});
