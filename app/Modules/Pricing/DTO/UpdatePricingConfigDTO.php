<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use App\Modules\Ride\Model\Enums\VehicleType;
use Illuminate\Http\Request;

final class UpdatePricingConfigDTO
{
    public function __construct(
        public readonly VehicleType $vehicleType,
        public readonly float       $basePrice,
        public readonly float       $distanceRate,
        public readonly float       $timeRate,
        public readonly float       $minFare,
        public readonly float       $surgeMultiplier,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vehicleType:     VehicleType::from((int) $request->input('vehicle_type')),
            basePrice:       (float) $request->input('base_price'),
            distanceRate:    (float) $request->input('distance_rate'),
            timeRate:        (float) $request->input('time_rate'),
            minFare:         (float) $request->input('min_fare'),
            surgeMultiplier: (float) $request->input('surge_multiplier', 1.0),
        );
    }
}
