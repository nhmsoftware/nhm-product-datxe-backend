<?php

declare(strict_types=1);

namespace App\Modules\Ride\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện được kích hoạt khi Admin gán tài xế cho chuyến xe đặt trước (UC-122).
 */
class RideAssignedByAdmin
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $rideId,
        public string $driverId,
        public string $customerId
    ) {
    }
}
