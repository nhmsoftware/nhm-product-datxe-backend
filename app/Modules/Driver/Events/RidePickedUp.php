<?php

declare(strict_types=1);

namespace App\Modules\Driver\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện phát ra khi tài xế xác nhận đã đón khách/lấy hàng thành công.
 */
final class RidePickedUp
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $rideId,
        public readonly int $driverId
    ) {
    }
}
