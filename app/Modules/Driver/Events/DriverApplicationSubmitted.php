<?php

declare(strict_types=1);

namespace App\Modules\Driver\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Phát sau khi hồ sơ đăng ký tài xế được tạo thành công.
 * UC-30 Normal Flow bước 18 — thông báo Admin có hồ sơ mới.
 */
final class DriverApplicationSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $applicationId,
        public readonly string $userId,
    ) {}
}
