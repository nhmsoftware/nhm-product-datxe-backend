<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

class RidePricingDTO
{
    public function __construct(
        public readonly float $basePrice,
        public readonly float $distancePrice,
        public readonly float $totalPrice
    ) {
    }
}
