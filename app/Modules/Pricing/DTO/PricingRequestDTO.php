<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

class PricingRequestDTO
{
    public function __construct(
        public readonly float $distance, // trong km
        public readonly float $duration, // trong gian ( phút)
        public readonly int $vehicleType,
        public readonly float $surgeMultiplier = 1.0,
        public readonly ?int $serviceType = null,
        public readonly ?string $rideMode = null,
        public readonly ?string $airportId = null,
        public readonly bool $allowLegacyFallback = true,
    ) {
    }

    public static function create(
        float $distance,
        float $duration,
        int $vehicleType,
        float $surgeMultiplier = 1.0,
        ?int $serviceType = null,
        ?string $rideMode = null,
        ?string $airportId = null,
        bool $allowLegacyFallback = true,
    ): self {
        return new self($distance, $duration, $vehicleType, $surgeMultiplier, $serviceType, $rideMode, $airportId, $allowLegacyFallback);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            distance: (float) $data['distance'], // trong km
            duration: (float) $data['duration'], // trong gian ( phút)
            vehicleType: (int) $data['vehicle_type_id'],
            surgeMultiplier: (float) ($data['surge_multiplier'] ?? 1.0),
            serviceType: isset($data['service_type']) ? (int) $data['service_type'] : null,
            rideMode: $data['ride_mode'] ?? null,
            airportId: isset($data['airport_id']) ? (string) $data['airport_id'] : null,
            allowLegacyFallback: (bool) ($data['allow_legacy_fallback'] ?? true),
        );
    }
}
