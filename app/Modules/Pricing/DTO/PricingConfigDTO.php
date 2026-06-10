<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use App\Modules\Pricing\Model\PricingConfig;

final class PricingConfigDTO
{
    public function __construct(
        public readonly string $id,
        public readonly int    $vehicleType,
        public readonly string $vehicleCode,
        public readonly string $vehicleLabel,
        public readonly bool   $isActive,
        public readonly float  $basePrice,
        public readonly float  $distanceRate,
        public readonly float  $timeRate,
        public readonly float  $minFare,
        public readonly float  $surgeMultiplier,
    ) {}

    public static function fromModel(PricingConfig $model): self
    {
        return new self(
            id:              $model->id,
            vehicleType:     (int) ($model->vehicle_type_id ?? $model->vehicle_type),
            vehicleCode:     $model->vehicleTypeRef?->code ?? ('vehicle_' . ($model->vehicle_type_id ?? $model->vehicle_type)),
            vehicleLabel:    $model->vehicleTypeRef?->name_vi ?? ('Loại xe #' . ($model->vehicle_type_id ?? $model->vehicle_type)),
            isActive:        (bool) ($model->is_active ?? true),
            basePrice:       (float) $model->base_price,
            distanceRate:    (float) $model->distance_rate,
            timeRate:        (float) $model->time_rate,
            minFare:         (float) $model->min_fare,
            surgeMultiplier: (float) $model->surge_multiplier,
        );
    }
}
