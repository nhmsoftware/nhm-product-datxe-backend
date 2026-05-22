<?php

use App\Core\Http\Controllers\FileServeController;
use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Nhóm các route yêu cầu phải đăng nhập
Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Phục vụ tất cả file private từ local disk (banner, news, menu, proof, v.v.)
    Route::get('/v1/files/serve', [FileServeController::class, 'serve'])
        ->name('files.serve');
});
