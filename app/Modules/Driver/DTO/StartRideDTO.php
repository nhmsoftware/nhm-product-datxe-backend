<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

/**
 * DTO chứa thông tin để bắt đầu thực hiện chuyến đi.
 */
final readonly class StartRideDTO
{
    public function __construct(
        public string $rideId,
        public string $userId,
        public float $currentLat,
        public float $currentLng,
    ) {
    }
}
