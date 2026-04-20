<?php

declare(strict_types=1);

namespace App\Modules\Operation\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Operation\Interfaces\OperationServiceInterface;
use App\Modules\Operation\Interfaces\LocationRepositoryInterface;
use App\Modules\Operation\Services\OperationService;
use App\Modules\Operation\Repositories\LocationRepository;
use App\Modules\Operation\Events\UserLocationUpdated;
use App\Modules\Operation\Interfaces\DispatchServiceInterface;
use App\Modules\Operation\Listeners\NotifyRealtimeOnLocationUpdated;
use App\Modules\Operation\Listeners\StartRideDispatching;
use App\Modules\Operation\Services\DispatchService;
use App\Modules\Ride\Events\RideBooked;
use Illuminate\Support\Facades\Event;

/**
 * Service Provider cho module Operation.
 * Đăng ký các Interface và tự động load Routes.
 */
class OperationServiceProvider extends BaseModuleServiceProvider
{
    /**
     * @inheritDoc
     */
    protected function getModuleName(): string
    {
        return 'Operation';
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->singleton(OperationServiceInterface::class, OperationService::class);
        $this->app->singleton(DispatchServiceInterface::class, DispatchService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();

        Event::listen(
            UserLocationUpdated::class,
            NotifyRealtimeOnLocationUpdated::class
        );

        Event::listen(
            RideBooked::class,
            StartRideDispatching::class
        );
    }
}
