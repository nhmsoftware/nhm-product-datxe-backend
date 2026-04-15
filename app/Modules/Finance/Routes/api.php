<?php

declare(strict_types=1);

namespace App\Modules\Finance\Routes;

use App\Modules\Finance\Http\Controllers\RewardController;
use App\Modules\Finance\Http\Controllers\SpendingController;
use App\Modules\Finance\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/vouchers')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    Route::get('/', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('{id}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::post('{id}/save', [VoucherController::class, 'save'])->name('vouchers.save');

    // UC-22: Apply Voucher quick action
    Route::post('{id}/apply-quick', [VoucherController::class, 'applyQuick'])->name('vouchers.apply-quick');
});

Route::prefix('v1/finance')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    // UC-23: View Spending Summary
    Route::get('spending-summary', [SpendingController::class, 'viewSummary'])->name('finance.spending-summary');

    // UC-24: View Reward History
    Route::get('rewards/overview', [RewardController::class, 'overview'])->name('finance.rewards.overview');
    Route::get('rewards/history', [RewardController::class, 'history'])->name('finance.rewards.history');
    Route::get('rewards/history/{transactionId}', [RewardController::class, 'showDetail'])->name('finance.rewards.detail');
});
