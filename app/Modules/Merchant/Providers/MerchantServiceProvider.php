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
    }

    public function boot(): void
    {
        parent::boot();
    }
}
