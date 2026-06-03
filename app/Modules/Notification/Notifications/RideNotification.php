<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class RideNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param string $message
     * @param string $category
     * @param array $extraData
     */
    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly string $category = 'order',
        private readonly array $extraData = []
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'title'       => $this->title,
            'message'     => $this->message,
            'category'    => $this->category,
            'occurred_at' => now()->toIso8601String(),
        ], $this->extraData);
    }
}
