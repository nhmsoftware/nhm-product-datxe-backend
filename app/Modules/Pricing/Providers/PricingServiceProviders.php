<?php


namespace App\Modules\Pricing\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Pricing\Services\PricingService;
use App\Modules\User\Http\Middleware\CheckAccountStatus;
use Illuminate\Routing\Router;

class PricingServiceProviders extends BaseModuleServiceProvider
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
