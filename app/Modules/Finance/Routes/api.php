<?php

declare(strict_types=1);

namespace App\Modules\Finance\Routes;

use App\Modules\Finance\Http\Controllers\AdminPaymentMethodController;
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
    Route::get('wallet/manage', [WalletController::class, 'manage'])->name('finance.wallet.manage');

    // UC-44: View Credit Wallet
    Route::get('wallet/credit', [WalletController::class, 'viewCreditWallet'])->name('finance.wallet.credit');
    Route::get('wallet/transactions/{transactionId}', [WalletController::class, 'getTransactionDetail'])->name('finance.wallet.transaction-detail');

    // UC-45: Top Up
    Route::get('wallet/top-up/options', [WalletController::class, 'getTopUpOptions'])->name('finance.wallet.top-up.options');      // B1: Màn hình nạp tiền
    Route::post('wallet/top-up', [WalletController::class, 'initiateTopUp'])->name('finance.wallet.top-up.initiate');               // B5: Xác nhận nạp tiền
    Route::get('wallet/top-up/{topUpId}', [WalletController::class, 'getTopUpDetail'])->name('finance.wallet.top-up.detail');       // Chi tiết giao dịch
    Route::delete('wallet/top-up/{topUpId}', [WalletController::class, 'cancelTopUp'])->name('finance.wallet.top-up.cancel');       // A4: Hủy giao dịch

    // UC-46: Subscription Packages
    Route::get('subscriptions/packages', [\App\Modules\Finance\Http\Controllers\SubscriptionController::class, 'packages'])->name('finance.subscriptions.packages');
    Route::post('subscriptions/register', [\App\Modules\Finance\Http\Controllers\SubscriptionController::class, 'register'])->name('finance.subscriptions.register');
});

// UC-45: Webhook/Callback từ Payment Gateway — PUBLIC (không cần auth, gateway không có Bearer token)
Route::prefix('v1/finance')->group(function () {
    // MoMo callback
    Route::post('wallet/top-up/callback/momo', [WalletController::class, 'callbackMomo'])->name('finance.wallet.top-up.callback.momo');
    // ZaloPay callback
    Route::post('wallet/top-up/callback/zalopay', [WalletController::class, 'callbackZalopay'])->name('finance.wallet.top-up.callback.zalopay');
    // payOS callback
    Route::post('wallet/top-up/callback/payos', [WalletController::class, 'callbackPayos'])->name('finance.wallet.top-up.callback.payos');
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

    // UC-109: Handle Refund
    Route::get('refunds', [\App\Modules\Finance\Http\Controllers\RefundController::class, 'index'])->name('admin.finance.refunds.index');
    Route::get('refunds/{id}', [\App\Modules\Finance\Http\Controllers\RefundController::class, 'show'])->name('admin.finance.refunds.show');
    Route::post('refunds/{id}/process', [\App\Modules\Finance\Http\Controllers\RefundController::class, 'process'])->name('admin.finance.refunds.process');

    // UC-116: Manage Driver Financial Model
    Route::get('driver-summary', [\App\Modules\Finance\Http\Controllers\AdminDriverFinanceController::class, 'summary'])->name('admin.finance.driver-summary');

    // UC-116 Extended: Báo cáo tài chính chi tiết
    Route::get('reports', [\App\Modules\Finance\Http\Controllers\AdminDriverFinanceController::class, 'reports'])->name('admin.finance.reports');

    // UC-117: Configure Credit Wallet
    Route::get('credit-wallet-config', [\App\Modules\Finance\Http\Controllers\AdminCreditWalletConfigController::class, 'show'])->name('admin.finance.credit-wallet-config.show');
    Route::post('credit-wallet-config', [\App\Modules\Finance\Http\Controllers\AdminCreditWalletConfigController::class, 'update'])->name('admin.finance.credit-wallet-config.update');

    // UC-118: Configure Subscription Package
    Route::get('subscriptions/packages', [\App\Modules\Finance\Http\Controllers\AdminSubscriptionController::class, 'index'])->name('admin.finance.subscriptions.packages.index');
    Route::post('subscriptions/packages', [\App\Modules\Finance\Http\Controllers\AdminSubscriptionController::class, 'store'])->name('admin.finance.subscriptions.packages.store');
    Route::put('subscriptions/packages/{id}', [\App\Modules\Finance\Http\Controllers\AdminSubscriptionController::class, 'update'])->name('admin.finance.subscriptions.packages.update');
    Route::patch('subscriptions/packages/{id}/disable', [\App\Modules\Finance\Http\Controllers\AdminSubscriptionController::class, 'disable'])->name('admin.finance.subscriptions.packages.disable');

    // UC-132: Configure Top-up Payment Methods (Admin cấu hình phương thức nạp tiền)
    Route::get('payment-methods', [AdminPaymentMethodController::class, 'index'])->name('admin.finance.payment-methods.index');
    Route::post('payment-methods', [AdminPaymentMethodController::class, 'store'])->name('admin.finance.payment-methods.store');
    Route::put('payment-methods/{id}', [AdminPaymentMethodController::class, 'update'])->name('admin.finance.payment-methods.update');
    Route::patch('payment-methods/{id}/toggle', [AdminPaymentMethodController::class, 'toggle'])->name('admin.finance.payment-methods.toggle');
});
