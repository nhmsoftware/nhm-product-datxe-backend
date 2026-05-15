<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Notification\Events\NotificationReadStatusUpdated;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;
use App\Modules\Notification\Listeners\NotifyRealtimeOnNotificationRead;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Notification\Services\NotificationService;
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
            \App\Modules\Notification\Interfaces\PushNotificationServiceInterface::class,
            \App\Modules\Notification\Services\PushNotificationService::class
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
            \Illuminate\Notifications\Events\NotificationSent::class,
            \App\Modules\Notification\Listeners\NotifyRealtimeOnNotificationSent::class
        );

        Event::listen(
            \Illuminate\Notifications\Events\NotificationSent::class,
            \App\Modules\Notification\Listeners\SendPushOnNotificationSent::class
        );

        Event::listen(
            \App\Modules\Ride\Events\RideAcceptedByDriver::class,
            \App\Modules\Notification\Listeners\RidePushNotificationListener::class
        );

        Event::listen(
            \App\Modules\Ride\Events\RideCanceled::class,
            \App\Modules\Notification\Listeners\RidePushNotificationListener::class
        );
    }
}
