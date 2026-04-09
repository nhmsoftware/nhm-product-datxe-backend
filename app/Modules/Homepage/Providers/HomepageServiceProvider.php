<?php

declare(strict_types=1);

namespace App\Modules\Homepage\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Homepage\Interfaces\HomepageServiceInterface;
use App\Modules\Homepage\Services\HomepageService;

class HomepageServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Homepage';
    }

    public function register(): void
    {
        // ── Services ──────
        $this->app->singleton(HomepageServiceInterface::class, HomepageService::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
