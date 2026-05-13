<?php

declare(strict_types=1);

namespace App\Modules\Finance\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Finance\Interfaces\RewardRepositoryInterface;
use App\Modules\Finance\Interfaces\RewardServiceInterface;
use App\Modules\Finance\Interfaces\RewardWalletRepositoryInterface;
use App\Modules\Finance\Interfaces\SpendingServiceInterface;
use App\Modules\Finance\Interfaces\VoucherRepositoryInterface;
use App\Modules\Finance\Interfaces\VoucherServiceInterface;
use App\Modules\Finance\Interfaces\VoucherWalletRepositoryInterface;
use App\Modules\Finance\Interfaces\TopUpRepositoryInterface;
use App\Modules\Finance\Interfaces\SubscriptionPackageRepositoryInterface;
use App\Modules\Finance\Interfaces\DriverSubscriptionRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletTransactionRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletServiceInterface;
use App\Modules\Finance\Interfaces\SubscriptionServiceInterface;
use App\Modules\Finance\Interfaces\FinanceRealtimeInterface;
use App\Modules\Finance\Interfaces\AdminVoucherServiceInterface;
use App\Modules\Finance\Interfaces\CommissionRuleRepositoryInterface;
use App\Modules\Finance\Interfaces\CommissionRuleServiceInterface;
use App\Modules\Finance\Repositories\RewardRepository;
use App\Modules\Finance\Repositories\RewardWalletRepository;
use App\Modules\Finance\Repositories\VoucherRepository;
use App\Modules\Finance\Repositories\VoucherWalletRepository;
use App\Modules\Finance\Repositories\TopUpRepository;
use App\Modules\Finance\Repositories\SubscriptionPackageRepository;
use App\Modules\Finance\Repositories\DriverSubscriptionRepository;
use App\Modules\Finance\Repositories\WalletRepository;
use App\Modules\Finance\Repositories\WalletTransactionRepository;
use App\Modules\Finance\Services\RewardService;
use App\Modules\Finance\Services\SpendingService;
use App\Modules\Finance\Services\VoucherService;
use App\Modules\Finance\Services\RedisFinanceRealtimeService;
use App\Modules\Finance\Services\WalletService;
use App\Modules\Finance\Services\SubscriptionService;
use App\Modules\Finance\Services\AdminVoucherService;
use App\Modules\Finance\Services\CommissionRuleService;
use App\Modules\Finance\Repositories\CommissionRuleRepository;
use App\Modules\Finance\Interfaces\RefundRepositoryInterface;
use App\Modules\Finance\Interfaces\RefundServiceInterface;
use App\Modules\Finance\Services\RefundService;
use App\Modules\Finance\Interfaces\AdminDriverFinanceServiceInterface;
use App\Modules\Finance\Services\AdminDriverFinanceService;
use App\Modules\Finance\Interfaces\CreditWalletConfigRepositoryInterface;
use App\Modules\Finance\Repositories\CreditWalletConfigRepository;
use App\Modules\Finance\Interfaces\CreditWalletConfigServiceInterface;
use App\Modules\Finance\Services\CreditWalletConfigService;
use App\Modules\Finance\Interfaces\AdminSubscriptionServiceInterface;
use App\Modules\Finance\Services\AdminSubscriptionService;

use App\Modules\User\Http\Middleware\CheckAccountStatus;
use Illuminate\Routing\Router;

final class FinanceServiceProvider extends BaseModuleServiceProvider
{

    protected function getModuleName(): string
    {
        return 'Finance';
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Repositories ──────
        $this->app->singleton(VoucherRepositoryInterface::class, VoucherRepository::class);
        $this->app->singleton(VoucherWalletRepositoryInterface::class, VoucherWalletRepository::class);
        $this->app->singleton(RewardRepositoryInterface::class, RewardRepository::class);
        $this->app->singleton(RewardWalletRepositoryInterface::class, RewardWalletRepository::class);
        $this->app->singleton(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->singleton(WalletTransactionRepositoryInterface::class, WalletTransactionRepository::class);
        $this->app->singleton(TopUpRepositoryInterface::class, TopUpRepository::class);
        $this->app->singleton(SubscriptionPackageRepositoryInterface::class, SubscriptionPackageRepository::class);
        $this->app->singleton(DriverSubscriptionRepositoryInterface::class, DriverSubscriptionRepository::class);
        $this->app->singleton(CommissionRuleRepositoryInterface::class, CommissionRuleRepository::class);
        $this->app->singleton(RefundRepositoryInterface::class, RefundRepository::class);
        $this->app->singleton(CreditWalletConfigRepositoryInterface::class, CreditWalletConfigRepository::class);


        // ── Services ──────
        $this->app->singleton(VoucherServiceInterface::class, VoucherService::class);
        $this->app->singleton(SpendingServiceInterface::class, SpendingService::class);
        $this->app->singleton(RewardServiceInterface::class, RewardService::class);
        $this->app->singleton(WalletServiceInterface::class, WalletService::class);
        $this->app->singleton(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->singleton(FinanceRealtimeInterface::class, RedisFinanceRealtimeService::class);
        $this->app->singleton(AdminVoucherServiceInterface::class, AdminVoucherService::class);
        $this->app->singleton(CommissionRuleServiceInterface::class, CommissionRuleService::class);
        $this->app->singleton(RefundServiceInterface::class, RefundService::class);
        $this->app->singleton(AdminDriverFinanceServiceInterface::class, AdminDriverFinanceService::class);
        $this->app->singleton(CreditWalletConfigServiceInterface::class, CreditWalletConfigService::class);
        $this->app->singleton(AdminSubscriptionServiceInterface::class, AdminSubscriptionService::class);


    }

    /**
     * Register the module's routes.
     */
    public function boot(): void
    {
        // Đăng ký middleware kiểm tra trạng thái tài khoản
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('check.account.status', CheckAccountStatus::class);

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Finance\Events\RefundProcessed::class,
            \App\Modules\Finance\Listeners\NotifyRealtimeOnRefundProcessed::class
        );

        parent::boot();

    }
}
