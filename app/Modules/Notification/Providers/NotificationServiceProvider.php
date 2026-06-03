<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Core\Providers\BaseModuleServiceProvider;
use App\Modules\Notification\Events\NotificationReadStatusUpdated;
use App\Modules\Notification\Interfaces\NotificationRepositoryInterface;
use App\Modules\Notification\Interfaces\NotificationServiceInterface;
use App\Modules\Notification\Interfaces\PushNotificationServiceInterface;
use App\Modules\Notification\Listeners\NotificationEventSubscriber;
use App\Modules\Notification\Listeners\NotifyRealtimeOnNotificationRead;
use App\Modules\Notification\Listeners\NotifyRealtimeOnNotificationSent;
use App\Modules\Notification\Listeners\SendPushOnNotificationSent;
use App\Modules\Notification\Notifications\SystemNotification;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Notification\Services\PushNotificationService;
use App\Modules\User\Model\User;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        Relation::enforceMorphMap([
            'user' => User::class,
            'system_notification' => SystemNotification::class,
        ]);

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

        // Event Subscriber cho toàn bộ các thông báo
        Event::subscribe(NotificationEventSubscriber::class);
    }
}
