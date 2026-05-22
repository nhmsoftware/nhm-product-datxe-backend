<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Marketing\Http\Controllers\AdminBannerController;
use App\Modules\Marketing\Http\Controllers\AdminNewsController;

Route::prefix('v1/admin/marketing')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Banners
        Route::prefix('banners')->group(function () {
            Route::get('/', [AdminBannerController::class, 'index'])->name('admin.marketing.banners.index');
            Route::post('/', [AdminBannerController::class, 'store'])->name('admin.marketing.banners.store');
            Route::get('{id}', [AdminBannerController::class, 'show'])->name('admin.marketing.banners.show');
            Route::match(['post', 'put'], '{id}', [AdminBannerController::class, 'update'])->name('admin.marketing.banners.update');
            Route::delete('{id}', [AdminBannerController::class, 'destroy'])->name('admin.marketing.banners.destroy');
        });

        // News
        Route::prefix('news')->group(function () {
            Route::get('/', [AdminNewsController::class, 'index'])->name('admin.marketing.news.index');
            Route::post('/', [AdminNewsController::class, 'store'])->name('admin.marketing.news.store');
            Route::get('{id}', [AdminNewsController::class, 'show'])->name('admin.marketing.news.show');
            Route::match(['post', 'put'], '{id}', [AdminNewsController::class, 'update'])->name('admin.marketing.news.update');
            Route::delete('{id}', [AdminNewsController::class, 'destroy'])->name('admin.marketing.news.destroy');
        });
    });
