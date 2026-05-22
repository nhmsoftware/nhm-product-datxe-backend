<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class NotifyRealtimeOnNotificationSent implements ShouldQueue
{
    public function handle(NotificationSent $event): void
    {
        try {
            $user = $event->notifiable;
            $notification = $event->notification;

            // Standard Database Notification data
            $data = $notification->toArray($user);

            $payload = [
                'event' => 'notification.created',
                'user_id' => (string) $user->id,
                'notification' => [
                    'id' => $event->response, // Standardly notification ID
                    'type' => get_class($notification),
                    'title' => $data['title'] ?? '',
                    'content' => $data['content'] ?? '',
                    'icon' => $data['icon'] ?? '',
                    'category' => $data['category'] ?? 'system',
                    'created_at' => now()->toIso8601String(),
                ],
                'unread_count' => method_exists($user, 'unreadNotifications') ? $user->unreadNotifications()->count() : 0,
                'occurred_at' => now()->toIso8601String(),
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Published notification.created to Redis', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnNotificationSent failed', ['error' => $e->getMessage()]);
        }
    }
}
