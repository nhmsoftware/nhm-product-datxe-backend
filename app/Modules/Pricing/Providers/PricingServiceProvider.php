<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Pricing\Interfaces\PricingConfigRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Pricing\Interfaces\PricingSurgeRuleRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingConfigHistoryRepositoryInterface;
use App\Modules\Pricing\Repositories\PricingConfigRepository;
use App\Modules\Pricing\Repositories\PricingGlobalSettingRepository;
use App\Modules\Pricing\Repositories\PricingSurgeRuleRepository;
use App\Modules\Pricing\Repositories\PricingConfigHistoryRepository;
use App\Modules\Pricing\Repositories\ScheduledPricingRepository;
use App\Modules\Pricing\Services\PricingService;
use App\Modules\Pricing\Services\ScheduledPricingService;
use App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface;
use App\Modules\Pricing\Interfaces\ScheduledPricingServiceInterface;
use App\Modules\User\Http\Middleware\CheckAccountStatus;
use Illuminate\Routing\Router;

final class PricingServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Pricing';
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PricingServiceInterface::class, PricingService::class);
        $this->app->singleton(PricingConfigRepositoryInterface::class, PricingConfigRepository::class);
        $this->app->singleton(PricingConfigHistoryRepositoryInterface::class, PricingConfigHistoryRepository::class);
        $this->app->singleton(PricingGlobalSettingRepositoryInterface::class, PricingGlobalSettingRepository::class);
        $this->app->singleton(PricingSurgeRuleRepositoryInterface::class, PricingSurgeRuleRepository::class);
        $this->app->singleton(ScheduledPricingRepositoryInterface::class, ScheduledPricingRepository::class);
        $this->app->singleton(ScheduledPricingServiceInterface::class, ScheduledPricingService::class);
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
            \App\Modules\Pricing\Events\PricingConfigUpdated::class,
            \App\Modules\Pricing\Listeners\LogPricingConfigHistory::class
        );

        parent::boot();
    }
}
