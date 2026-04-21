<?php

declare(strict_types=1);

namespace App\Modules\Ride\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\Repositories\RideRepository;
use App\Modules\Ride\Services\GoongMapService;
use App\Modules\Ride\Services\RideService;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Events\RideCancellationRequested;
use App\Modules\Ride\Events\RideCancellationResponded;
use App\Modules\Ride\Listeners\NotifyRealtimeOnRideCanceled;
use App\Modules\Ride\Listeners\NotifyRealtimeOnRideCancellationRequested;
use App\Modules\Ride\Listeners\NotifyRealtimeOnRideCancellationResponded;
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

        // ── Services ──────
        $this->app->singleton(RideServiceInterface::class, RideService::class);
        $this->app->singleton(MapServiceInterface::class, GoongMapService::class);
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

        parent::boot();
    }
}
