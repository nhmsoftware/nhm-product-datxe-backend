<?php

declare(strict_types=1);

namespace App\Modules\Ride\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện xảy ra khi một chuyến xe bị hủy.
 * Dùng để thông báo cho tài xế hoặc xử lý hoàn tiền nếu cần.
 */
final class RideCanceled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $rideId,
        public readonly int $customerId,
        public readonly ?int $driverId = null,
    ) {
    }
}
