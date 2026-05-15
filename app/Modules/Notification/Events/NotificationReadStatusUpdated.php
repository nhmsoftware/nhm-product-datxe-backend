<?php

declare(strict_types=1);

namespace App\Modules\Notification\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NotificationReadStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly int $unreadCount,
    ) {}
}
