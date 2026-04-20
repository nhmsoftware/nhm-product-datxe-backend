<?php

declare(strict_types=1);

namespace App\Modules\Driver\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event phát ra khi Admin phê duyệt hồ sơ đăng ký tài xế thành công.
 * UC-30 Alternative Flow — nâng cấp user & thông báo realtime cho frontend.
 */
final class DriverApplicationApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int|string $applicationId,
        public readonly int|string $userId,
    ) {}
}
