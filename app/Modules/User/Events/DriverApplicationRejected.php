<?php

declare(strict_types=1);

namespace App\Modules\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện xảy ra khi hồ sơ đăng ký tài xế bị từ chối.
 * UC-82 Reject Driver
 */
final class DriverApplicationRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string|int $userId,
        public readonly string     $reason,
    ) {}
}
