<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Driver\Events\RideCancelled;
use App\Modules\Notification\Events\NotificationReadStatusUpdated;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;
use App\Modules\Notification\Interfaces\PushNotificationServiceInterface;
use App\Modules\Notification\Listeners\NotifyRealtimeOnNotificationRead;
use App\Modules\Notification\Listeners\NotifyRealtimeOnNotificationSent;
use App\Modules\Notification\Listeners\RidePushNotificationListener;
use App\Modules\Notification\Listeners\SendPushOnNotificationSent;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Notification\Services\PushNotificationService;
use App\Modules\Ride\Events\RideAcceptedByDriver;
use App\Modules\Ride\Events\RideCanceled;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;

final class NotificationServiceProvider extends BaseModuleServiceProvider
{
    protected function getModuleName(): string
    {
        return 'Notification';
    }

    public function register(): void
    {
        $this->app->singleton(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->singleton(NotificationServiceInterface::class, NotificationService::class);
        $this->app->singleton(
            PushNotificationServiceInterface::class,
            PushNotificationService::class
        );
    }

    public function boot(): void
    {
        parent::boot();

        // Mapping Events to Listeners
        Event::listen(
            NotificationReadStatusUpdated::class,
            NotifyRealtimeOnNotificationRead::class
        );

        Event::listen(
            NotificationSent::class,
            NotifyRealtimeOnNotificationSent::class
        );

        Event::listen(
            NotificationSent::class,
            SendPushOnNotificationSent::class
        );

        Event::listen(
            RideAcceptedByDriver::class,
            RidePushNotificationListener::class
        );

        Event::listen(
            RideCanceled::class,
            RidePushNotificationListener::class
        );

        Event::listen(
            RideCancelled::class,
            RidePushNotificationListener::class
        );
    }
}
