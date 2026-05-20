<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Merchant\Interfaces\MerchantRegistrationServiceInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\Merchant\Repositories\MerchantRepository;
use App\Modules\Merchant\Services\MerchantRegistrationService;

final class MerchantServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Merchant';
    }

    public function register(): void
    {
        // Repositories
        $this->app->singleton(
            MerchantRepositoryInterface::class,
            MerchantRepository::class
        );

        // Services
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\MerchantRegistrationServiceInterface::class,
            \App\Modules\Merchant\Services\MerchantRegistrationService::class
        );

        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\MerchantStoreServiceInterface::class,
            \App\Modules\Merchant\Services\MerchantStoreService::class
        );

        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\MerchantAdminServiceInterface::class,
            \App\Modules\Merchant\Services\MerchantAdminService::class
        );

        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\CustomerMerchantServiceInterface::class,
            \App\Modules\Merchant\Services\CustomerMerchantService::class
        );

        // Menu Management
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\MenuRepositoryInterface::class,
            \App\Modules\Merchant\Repositories\MenuRepository::class
        );
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface::class,
            \App\Modules\Merchant\Repositories\MenuItemRepository::class
        );
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\MenuServiceInterface::class,
            \App\Modules\Merchant\Services\MenuService::class
        );
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\MerchantMenuEditLogRepositoryInterface::class,
            \App\Modules\Merchant\Repositories\MerchantMenuEditLogRepository::class
        );
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\AdminMenuServiceInterface::class,
            \App\Modules\Merchant\Services\AdminMenuService::class
        );

        // Combo Management
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\ComboRepositoryInterface::class,
            \App\Modules\Merchant\Repositories\ComboRepository::class
        );
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\ComboItemRepositoryInterface::class,
            \App\Modules\Merchant\Repositories\ComboItemRepository::class
        );
        $this->app->singleton(
            \App\Modules\Merchant\Interfaces\ComboServiceInterface::class,
            \App\Modules\Merchant\Services\ComboService::class
        );
    }

    public function boot(): void
    {
        parent::boot();
    }
}
