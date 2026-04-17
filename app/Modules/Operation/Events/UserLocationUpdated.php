<?php

declare(strict_types=1);

namespace App\Modules\Operation\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện cập nhật vị trí người dùng (UC-35).
 * Được broadcast qua Redis để các client (Driver/Customer) cập nhật trên bản đồ.
 */
final class UserLocationUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $role,
        public readonly float $lat,
        public readonly float $lng,
        public readonly ?int $rideId = null, // Gắn kèm ID chuyến xe nếu đang trong chuyến
    ) {
    }
}
