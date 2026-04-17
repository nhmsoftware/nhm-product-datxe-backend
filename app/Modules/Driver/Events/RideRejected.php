<?php

declare(strict_types=1);

namespace App\Modules\Driver\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event phát ra khi tài xế từ chối một đơn hàng (Ride) đang ở trạng thái Pending.
 * Dùng để thông báo cho hệ thống điều phối hoặc khách hàng qua realtime.
 */
final class RideRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $rideId,
        public readonly int $driverId
    ) {}
}
