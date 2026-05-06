<?php

declare(strict_types=1);

namespace App\Modules\Ride\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện được kích hoạt khi Admin đẩy danh sách chuyến xe đặt trước ra pool (UC-122).
 */
class ScheduledRidesPushedToPool
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $rideIds
    ) {
    }
}
