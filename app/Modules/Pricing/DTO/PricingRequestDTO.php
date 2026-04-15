<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use App\Modules\Ride\Model\Enums\VehicleType;

class PricingRequestDTO
{
    public function __construct(
        public readonly float $distance, // trong km
        public readonly float $duration, // trong gian ( phút)
        public readonly int $vehicleType, // từ vehicleType Enum
        public readonly float $surgeMultiplier = 1.0
    ) {
    }

    public static function create(
        float $distance,
        float $duration,
        int $vehicleType,
        float $surgeMultiplier = 1.0
    ): self {
        return new self($distance, $duration, $vehicleType, $surgeMultiplier);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            distance: (float) $data['distance'], // trong km
            duration: (float) $data['duration'], // trong gian ( phút)
            vehicleType: (int) $data['vehicle_type'],
            surgeMultiplier: (float) ($data['surge_multiplier'] ?? 1.0)
        );
    }
}
