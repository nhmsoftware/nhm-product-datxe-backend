<?php

declare(strict_types=1);

namespace App\Modules\Driver\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện phát ra khi trạng thái của Tài xế thay đổi (ACTIVE, BUSY, etc.)
 */
final class DriverStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $userId,
        public int $status, // DriverStatus Enum value
    ) {
    }
}
