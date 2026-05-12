<?php

declare(strict_types=1);

namespace App\Modules\Finance\Routes;

use App\Modules\Finance\Http\Controllers\RewardController;
use App\Modules\Finance\Http\Controllers\SpendingController;
use App\Modules\Finance\Http\Controllers\VoucherController;
use App\Modules\Finance\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/vouchers')->middleware(['auth:sanctum', 'check.account.status'])->group(function () {
    Route::get('my-vouchers', [VoucherController::class, 'myVouchers'])->name('vouchers.mine');
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

    // UC-43: Manage Wallet (Driver Dashboard)
    Route::get('wallet/manage', [\App\Modules\Finance\Http\Controllers\WalletController::class, 'manage'])->name('finance.wallet.manage');

    // UC-44: View Credit Wallet
    Route::get('wallet/credit', [\App\Modules\Finance\Http\Controllers\WalletController::class, 'viewCreditWallet'])->name('finance.wallet.credit');
    Route::get('wallet/transactions/{transactionId}', [\App\Modules\Finance\Http\Controllers\WalletController::class, 'getTransactionDetail'])->name('finance.wallet.transaction-detail');

    // UC-45: Top Up
    Route::post('wallet/top-up', [\App\Modules\Finance\Http\Controllers\WalletController::class, 'initiateTopUp'])->name('finance.wallet.top-up.initiate');
    Route::post('wallet/top-up/callback', [\App\Modules\Finance\Http\Controllers\WalletController::class, 'callback'])->name('finance.wallet.top-up.callback');

    // UC-46: Subscription Packages
    Route::get('subscriptions/packages', [\App\Modules\Finance\Http\Controllers\SubscriptionController::class, 'packages'])->name('finance.subscriptions.packages');
    Route::post('subscriptions/register', [\App\Modules\Finance\Http\Controllers\SubscriptionController::class, 'register'])->name('finance.subscriptions.register');
});

// Admin Routes
Route::prefix('v1/admin/finance')->middleware(['auth:sanctum'])->group(function () {
    // UC-99: Manage Voucher
    Route::get('vouchers', [\App\Modules\Finance\Http\Controllers\AdminVoucherController::class, 'index'])->name('admin.finance.vouchers.index');
    Route::get('vouchers/{id}', [\App\Modules\Finance\Http\Controllers\AdminVoucherController::class, 'show'])->name('admin.finance.vouchers.show');
    Route::post('vouchers', [\App\Modules\Finance\Http\Controllers\AdminVoucherController::class, 'store'])->name('admin.finance.vouchers.store');
    Route::put('vouchers/{id}', [\App\Modules\Finance\Http\Controllers\AdminVoucherController::class, 'update'])->name('admin.finance.vouchers.update');
    Route::delete('vouchers/{id}', [\App\Modules\Finance\Http\Controllers\AdminVoucherController::class, 'destroy'])->name('admin.finance.vouchers.destroy');
    Route::post('vouchers/assign', [\App\Modules\Finance\Http\Controllers\AdminVoucherController::class, 'assign'])->name('admin.finance.vouchers.assign');
    Route::patch('vouchers/{id}/deactivate', [\App\Modules\Finance\Http\Controllers\AdminVoucherController::class, 'deactivate'])->name('admin.finance.vouchers.deactivate');

    // UC-97: Configure Commission
    Route::get('commissions', [\App\Modules\Finance\Http\Controllers\AdminCommissionController::class, 'index'])->name('admin.finance.commissions.index');
    Route::post('commissions', [\App\Modules\Finance\Http\Controllers\AdminCommissionController::class, 'store'])->name('admin.finance.commissions.store');
    Route::delete('commissions/{id}', [\App\Modules\Finance\Http\Controllers\AdminCommissionController::class, 'destroy'])->name('admin.finance.commissions.destroy');
});
