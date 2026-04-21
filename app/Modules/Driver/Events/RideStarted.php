<?php

declare(strict_types=1);

namespace App\Modules\Driver\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện khi tài xế bắt đầu thực hiện chuyến đi (UC-35 Start Trip).
 */
final class RideStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId,
    ) {
    }
}
