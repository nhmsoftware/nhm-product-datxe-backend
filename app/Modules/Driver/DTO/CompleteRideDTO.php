<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

/**
 * DTO chứa thông tin để hoàn thành chuyến đi.
 */
final readonly class CompleteRideDTO
{
    public function __construct(
        public string $rideId,
        public string $userId,
        public float $currentLat,
        public float $currentLng,
    ) {
    }
}
