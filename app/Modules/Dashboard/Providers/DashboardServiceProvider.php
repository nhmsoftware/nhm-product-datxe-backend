<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Dashboard\Interfaces\DashboardServiceInterface;
use App\Modules\Dashboard\Services\DashboardService;

class DashboardServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Dashboard';
    }

    public function register(): void
    {
        $this->app->singleton(DashboardServiceInterface::class, DashboardService::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
