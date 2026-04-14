<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/auth', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

// Nhóm các route yêu cầu phải đăng nhập
Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
