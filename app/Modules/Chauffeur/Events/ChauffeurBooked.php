<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sự kiện khi một yêu cầu Lái hộ được tạo thành công.
 */
class ChauffeurBooked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $rideId,
        public string $customerId,
        public string $licensePlate,
        public string $vehicleType,
        public string $brand,
        public string $color
    ) {
    }
}
