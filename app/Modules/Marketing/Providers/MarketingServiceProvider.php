<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Marketing\Interfaces\BannerRepositoryInterface;
use App\Modules\Marketing\Interfaces\BannerServiceInterface;
use App\Modules\Marketing\Interfaces\NewsRepositoryInterface;
use App\Modules\Marketing\Interfaces\NewsServiceInterface;
use App\Modules\Marketing\Repositories\BannerRepository;
use App\Modules\Marketing\Repositories\NewsRepository;
use App\Modules\Marketing\Services\BannerService;
use App\Modules\Marketing\Services\NewsService;

class MarketingServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Marketing';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(BannerRepositoryInterface::class, BannerRepository::class);
        $this->app->singleton(BannerServiceInterface::class, BannerService::class);
        $this->app->singleton(NewsRepositoryInterface::class, NewsRepository::class);
        $this->app->singleton(NewsServiceInterface::class, NewsService::class);
    }

}
