<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\Notification\Interfaces\PushNotificationServiceInterface;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class SendPushOnNotificationSent implements ShouldQueue
{
    public function __construct(
        private readonly PushNotificationServiceInterface $pushNotificationService
    ) {}

    public function handle(NotificationSent $event): void
    {
        try {
            $user = $event->notifiable;
            $notification = $event->notification;
            
            // Lấy data từ notification
            $data = $notification->toDatabase($user);
            
            $title   = $data['title'] ?? 'Thông báo mới';
            $content = $data['content'] ?? '';
            $icon    = $data['icon'] ?? null;
            
            // Gửi push qua service
            $this->pushNotificationService->sendToUser(
                $user,
                $title,
                $content,
                $data, // Gửi toàn bộ data metadata
                $icon
            );
            
            Log::info('Triggered Push Notification via SendPushOnNotificationSent', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('SendPushOnNotificationSent failed', ['error' => $e->getMessage()]);
        }
    }
}
