<?php

declare(strict_types=1);

namespace App\Modules\Driver\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class DriverApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const NOTIFICATION_TITLE = 'Chúc mừng! Hồ sơ của bạn đã được duyệt';
    private const NOTIFICATION_BODY = 'Bạn hiện đã có thể bắt đầu nhận cuốc xe. Hãy chuyển sang trạng thái "Sẵn sàng" nhé!';

    public function __construct(
        private readonly int $applicationId
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->applicationId,
            'title'          => self::NOTIFICATION_TITLE,
            'message'        => self::NOTIFICATION_BODY,
            'type'           => 'driver_approved',
            'action'         => 'driver_status_toggle',
            'occurred_at'    => now()->toIso8601String(),
        ];
    }
}
