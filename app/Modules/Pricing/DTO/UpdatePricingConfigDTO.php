<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use Illuminate\Http\Request;

final class UpdatePricingConfigDTO
{
    public function __construct(
        public readonly int         $vehicleTypeId,
        public readonly float       $basePrice,
        public readonly float       $distanceRate,
        public readonly float       $timeRate,
        public readonly float       $minFare,
        public readonly float       $surgeMultiplier,
        public readonly float       $commissionRate,
        public readonly bool        $isActive,
        public readonly ?string     $adminId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vehicleTypeId:   (int) $request->input('vehicle_type_id'),
            basePrice:       (float) $request->input('base_price'),
            distanceRate:    (float) $request->input('distance_rate'),
            timeRate:        (float) $request->input('time_rate'),
            minFare:         (float) $request->input('min_fare'),
            surgeMultiplier: (float) $request->input('surge_multiplier', 1.0),
            commissionRate:  (float) $request->input('commission_rate', 20.0),
            isActive:        $request->boolean('is_active', true),
            adminId:         (string) $request->user()?->id,
        );
    }
}
