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
        public readonly float $finalFare
    ) {
    }

    public static function create(
        float $baseFare,
        float $distanceFare,
        float $timeFare,
        float $surgeMultiplier,
        float $originalFare,
        float $finalFare
    ): self {
        return new self(
            $baseFare,
            $distanceFare,
            $timeFare,
            $surgeMultiplier,
            $originalFare,
            $finalFare
        );
    }

    public function toArray(): array
    {
        return [
            'base_fare' => $this->baseFare,
            'distance_fare' => $this->distanceFare,
            'time_fare' => $this->timeFare,
            'surge_multiplier' => $this->surgeMultiplier,
            'original_fare' => $this->originalFare,
            'final_fare' => $this->finalFare,
        ];
    }
}
