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
        public readonly string $rideId,
        public readonly string $customerId,
        public readonly ?string $driverId = null,
        public readonly ?string $reason = null,
        public readonly ?string $canceledBy = null,
    ) {
    }
}
