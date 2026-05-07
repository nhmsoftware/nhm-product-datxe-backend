<?php

declare(strict_types=1);

namespace App\Modules\Ride\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Interfaces\RideCallLogRepositoryInterface;
use App\Modules\Ride\Interfaces\RideChatMessageRepositoryInterface;
use App\Modules\Ride\Interfaces\RideCommunicationRealtimeInterface;
use App\Modules\Ride\Interfaces\RideCommunicationServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface;
use App\Modules\Ride\Repositories\RideCallLogRepository;
use App\Modules\Ride\Repositories\RideChatMessageRepository;
use App\Modules\Ride\Repositories\RideRepository;
use App\Modules\Ride\Interfaces\AirportRepositoryInterface;
use App\Modules\Ride\Repositories\AirportRepository;
use App\Modules\Ride\Services\GoongMapService;
use App\Modules\Ride\Services\RedisRideCommunicationRealtimeService;
use App\Modules\Ride\Services\RideCommunicationService;
use App\Modules\Ride\Services\RedisRideTrackingRealtimeService;
use App\Modules\Ride\Services\RideService;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Events\RideCancellationRequested;
use App\Modules\Ride\Events\RideCancellationResponded;
use App\Modules\Ride\Listeners\NotifyRealtimeOnRideCanceled;
use App\Modules\Ride\Listeners\NotifyRealtimeOnRideCancellationRequested;
use App\Modules\Ride\Listeners\NotifyRealtimeOnRideCancellationResponded;
use App\Modules\Ride\Events\RideAssignedByAdmin;
use App\Modules\Ride\Events\ScheduledRidesPushedToPool;
use App\Modules\Ride\Listeners\NotifyRealtimeOnRideAssignedByAdmin;
use App\Modules\Ride\Listeners\NotifyRealtimeOnScheduledRidesPushed;
use App\Modules\User\Http\Middleware\CheckAccountStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Routing\Router;


class RideServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Ride';
    }

    public function register(): void
    {
        // ── Repositories ──
        $this->app->singleton(RideRepositoryInterface::class, RideRepository::class);
        $this->app->singleton(RideChatMessageRepositoryInterface::class, RideChatMessageRepository::class);
        $this->app->singleton(RideCallLogRepositoryInterface::class, RideCallLogRepository::class);
        $this->app->singleton(AirportRepositoryInterface::class, AirportRepository::class);

        // ── Services ──────
        $this->app->singleton(RideServiceInterface::class, RideService::class);
        $this->app->singleton(RideCommunicationServiceInterface::class, RideCommunicationService::class);
        $this->app->singleton(MapServiceInterface::class, GoongMapService::class);
        $this->app->singleton(RideTrackingRealtimeInterface::class, RedisRideTrackingRealtimeService::class);
        $this->app->singleton(RideCommunicationRealtimeInterface::class, RedisRideCommunicationRealtimeService::class);
    }

    public function boot(): void
    {
        // Đăng ký middleware kiểm tra trạng thái tài khoản
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('check.account.status', CheckAccountStatus::class);

        // Đăng ký Event Listeners cho Real-time
        Event::listen(
            RideCancellationRequested::class,
            NotifyRealtimeOnRideCancellationRequested::class
        );

        Event::listen(
            RideCancellationResponded::class,
            NotifyRealtimeOnRideCancellationResponded::class
        );

        Event::listen(
            RideCanceled::class,
            NotifyRealtimeOnRideCanceled::class
        );

        Event::listen(
            RideAssignedByAdmin::class,
            NotifyRealtimeOnRideAssignedByAdmin::class
        );

        Event::listen(
            ScheduledRidesPushedToPool::class,
            NotifyRealtimeOnScheduledRidesPushed::class
        );

        parent::boot();
    }
}
