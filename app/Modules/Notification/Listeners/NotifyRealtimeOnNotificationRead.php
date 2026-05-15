<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Events\NotificationReadStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class NotifyRealtimeOnNotificationRead implements ShouldQueue
{
    public function handle(NotificationReadStatusUpdated $event): void
    {
        try {
            $payload = [
                'event' => 'notification.unread_count_updated',
                'user_id' => $event->userId,
                'unread_count' => $event->unreadCount,
                'occurred_at' => now()->toIso8601String(),
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));
            
            Log::info('Published notification.unread_count_updated to Redis', ['user_id' => $event->userId]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnNotificationRead failed', ['error' => $e->getMessage()]);
        }
    }
}
