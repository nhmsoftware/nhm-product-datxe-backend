<?php

declare(strict_types=1);

namespace App\Modules\Finance\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Finance\Interfaces\VoucherRepositoryInterface;
use App\Modules\Finance\Interfaces\VoucherServiceInterface;
use App\Modules\Finance\Interfaces\VoucherWalletRepositoryInterface;
use App\Modules\Finance\Repositories\VoucherRepository;
use App\Modules\Finance\Repositories\VoucherWalletRepository;
use App\Modules\Finance\Services\VoucherService;
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

        // ── Services ──────
        $this->app->singleton(VoucherServiceInterface::class, VoucherService::class);
        $this->app->singleton(\App\Modules\Finance\Interfaces\SpendingServiceInterface::class, \App\Modules\Finance\Services\SpendingService::class);

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

        parent::boot();
    }
}
