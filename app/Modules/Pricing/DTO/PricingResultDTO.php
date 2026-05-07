<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

class PricingResultDTO
{
    public function __construct(
        public readonly float $baseFare,
        public readonly float $distanceFare,
        public readonly float $timeFare,
        public readonly float $surgeMultiplier,
        public readonly float $originalFare,
        public readonly float $finalFare,
        public readonly float $commissionRate,
        public readonly float $commissionFare,
    ) {
    }

    public static function create(
        float $baseFare,
        float $distanceFare,
        float $timeFare,
        float $surgeMultiplier,
        float $originalFare,
        float $finalFare,
        float $commissionRate = 0.0,
        float $commissionFare = 0.0
    ): self {
        return new self(
            $baseFare,
            $distanceFare,
            $timeFare,
            $surgeMultiplier,
            $originalFare,
            $finalFare,
            $commissionRate,
            $commissionFare
        );
    }

    public function toArray(): array
    {
        return [
            'base_fare'        => $this->baseFare,
            'distance_fare'    => $this->distanceFare,
            'time_fare'        => $this->timeFare,
            'surge_multiplier' => $this->surgeMultiplier,
            'original_fare'    => $this->originalFare,
            'final_fare'       => $this->finalFare,
            'commission_rate'  => $this->commissionRate,
            'commission_fare'  => $this->commissionFare,
        ];
    }
}
