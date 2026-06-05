<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Modules\Notification\Interfaces\PushNotificationServiceInterface;

final class NotifyRealtimeOnNotificationSent implements ShouldQueue
{
    public function __construct(
        private readonly PushNotificationServiceInterface $pushNotificationService
    ) {}

    public function handle(NotificationSent $event): void
    {
        try {
            // Chỉ xử lý 1 lần cho channel database để tránh trùng lặp nếu dùng nhiều channel
            if ($event->channel !== 'database') {
                return;
            }

            $user = $event->notifiable;
            $notification = $event->notification;

            // Standard Database Notification data
            $data = $notification->toArray($user);
            
            $title = $data['title'] ?? '';
            $content = $data['content'] ?? $data['message'] ?? '';
            $icon = $data['icon'] ?? '';

            // 1. Gửi Push Notification (Ngoài app - FCM)
            try {
                if (!empty($title) && !empty($content)) {
                    $this->pushNotificationService->sendToUser($user, $title, $content, $data, $icon);
                }
            } catch (\Exception $e) {
                Log::error('FCM Push Notification failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            }

            // 2. Publish Realtime Event (Trong app - Redis/Socket)
            $payload = [
                'event' => 'notification.created',
                'user_id' => (string) $user->id,
                'notification' => [
                    'id' => $event->response, // Standardly notification ID
                    'type' => get_class($notification),
                    'title' => $title,
                    'content' => $content,
                    'icon' => $icon,
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
