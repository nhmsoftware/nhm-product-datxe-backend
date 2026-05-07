<?php

declare(strict_types=1);

namespace App\Modules\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện xảy ra khi hồ sơ đăng ký tài xế được duyệt.
 * UC-81 Approve Driver
 */
final class DriverApplicationApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string|int $userId,
    ) {}
}
