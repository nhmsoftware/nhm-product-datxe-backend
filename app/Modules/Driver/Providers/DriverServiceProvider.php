<?php

declare(strict_types=1);

namespace App\Modules\Driver\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Repositories\DriverRegistrationRepository;
use App\Modules\Driver\Repositories\FileRecordRepository;
use App\Modules\Driver\Services\DriverRegistrationService;

class DriverServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Driver';
    }

    public function register(): void
    {
        // ── Repositories ─────────────────────────────────────────────────
        $this->app->singleton(
            DriverRegistrationRepositoryInterface::class,
            DriverRegistrationRepository::class
        );

        $this->app->singleton(
            FileRecordRepositoryInterface::class,
            FileRecordRepository::class
        );

        // ── Services ─────────────────────────────────────────────────────
        $this->app->singleton(
            DriverRegistrationServiceInterface::class,
            DriverRegistrationService::class
        );
    }

    public function boot(): void
    {
        parent::boot(); // Auto-load Routes/api.php và Config/
    }
}
