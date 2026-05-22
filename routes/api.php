<?php

use App\Core\Http\Controllers\FileServeController;
use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Serve file private (banner, news, menu, proof, v.v.) - không yêu cầu auth
// vì ảnh cần hiển thị trực tiếp qua thẻ <img> trong browser
Route::get('/v1/files/serve', [FileServeController::class, 'serve'])
    ->name('files.serve');

// Nhóm các route yêu cầu phải đăng nhập
Route::middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
});
