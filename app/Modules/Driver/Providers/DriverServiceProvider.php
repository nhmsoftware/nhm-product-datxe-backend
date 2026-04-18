<?php

declare(strict_types=1);

namespace App\Modules\Driver\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Driver\Events\RideRejected;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use App\Modules\Driver\Interfaces\DriverOperationServiceInterface;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Listeners\NotifyRealtimeOnDriverApproved;
use App\Modules\Driver\Listeners\NotifyRealtimeOnDriverArrived;
use App\Modules\Driver\Listeners\NotifyRealtimeOnRideAccepted;
use App\Modules\Driver\Listeners\NotifyRealtimeOnRideCancelled;
use App\Modules\Driver\Listeners\NotifyRealtimeOnRidePickedUp;
use App\Modules\Driver\Repositories\DriverRegistrationRepository;
use App\Modules\Driver\Repositories\FileRecordRepository;
use App\Modules\Driver\Services\DriverRegistrationService;
use App\Modules\Driver\Services\DriverOperationService;

use App\Modules\Driver\Events\DriverApplicationApproved;
use App\Modules\Driver\Events\DriverArrivedAtPickup;
use App\Modules\Driver\Events\RideAccepted;
use App\Modules\Driver\Events\RideCancelled;
use App\Modules\Driver\Events\RidePickedUp;
use App\Modules\Driver\Listeners\NotifyRealtimeOnRideRejected;
use App\Modules\Driver\Events\RideStarted;
use App\Modules\Driver\Events\RideCompleted;
use App\Modules\Driver\Listeners\NotifyRealtimeOnRideStarted;
use App\Modules\Driver\Listeners\NotifyRealtimeOnRideCompleted;
use Illuminate\Support\Facades\Event;

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

        $this->app->singleton(
            DriverOperationServiceInterface::class,
            DriverOperationService::class
        );
    }

    public function boot(): void
    {
        parent::boot(); // Auto-load Routes/api.php và Config/

        // Register event listeners
        Event::listen(
            RideAccepted::class,
            NotifyRealtimeOnRideAccepted::class
        );

        Event::listen(
            RideCancelled::class,
            NotifyRealtimeOnRideCancelled::class
        );

        Event::listen(
            DriverApplicationApproved::class,
            NotifyRealtimeOnDriverApproved::class
        );

        Event::listen(
            RideRejected::class,
            NotifyRealtimeOnRideRejected::class
        );

        Event::listen(
            RidePickedUp::class,
            NotifyRealtimeOnRidePickedUp::class
        );

        Event::listen(
            DriverArrivedAtPickup::class,
            NotifyRealtimeOnDriverArrived::class
        );

        Event::listen(
            RideStarted::class,
            NotifyRealtimeOnRideStarted::class
        );

        Event::listen(
            RideCompleted::class,
            NotifyRealtimeOnRideCompleted::class
        );
    }
}
