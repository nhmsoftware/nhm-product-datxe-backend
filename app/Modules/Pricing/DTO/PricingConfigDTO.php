<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use App\Modules\Pricing\Model\PricingConfig;

final class PricingConfigDTO
{
    public function __construct(
        public readonly string $id,
        public readonly int    $vehicleType,
        public readonly string $vehicleLabel,
        public readonly float  $basePrice,
        public readonly float  $distanceRate,
        public readonly float  $timeRate,
        public readonly float  $minFare,
        public readonly float  $surgeMultiplier,
        public readonly float  $commissionRate,
    ) {}

    public static function fromModel(PricingConfig $model): self
    {
        // Import enum for label
        $enum = \App\Modules\Ride\Model\Enums\VehicleType::from($model->vehicle_type);

        return new self(
            id:              $model->id,
            vehicleType:     $model->vehicle_type,
            vehicleLabel:    $enum->getLabel(),
            basePrice:       (float) $model->base_price,
            distanceRate:    (float) $model->distance_rate,
            timeRate:        (float) $model->time_rate,
            minFare:         (float) $model->min_fare,
            surgeMultiplier: (float) $model->surge_multiplier,
            commissionRate:  (float) $model->commission_rate,
        );
    }
}
