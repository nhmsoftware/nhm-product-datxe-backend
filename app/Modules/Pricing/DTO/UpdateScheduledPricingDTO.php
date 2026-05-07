<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use Illuminate\Http\Request;

final class UpdateScheduledPricingDTO
{
    public function __construct(
        public readonly float $basePrice,
        public readonly float $scheduledSurcharge,
        public readonly float $intercityBasePrice,
        public readonly float $airportBasePrice,
        public readonly int $dispatchMode,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            basePrice:          (float) $request->input('base_price'),
            scheduledSurcharge: (float) $request->input('scheduled_surcharge'),
            intercityBasePrice: (float) $request->input('intercity_base_price'),
            airportBasePrice:   (float) $request->input('airport_base_price'),
            dispatchMode:       (int) $request->input('dispatch_mode'),
        );
    }

    public function toPricingConfigArray(): array
    {
        return [
            'base_price'           => $this->basePrice,
            'scheduled_surcharge'  => $this->scheduledSurcharge,
            'intercity_base_price' => $this->intercityBasePrice,
            'airport_base_price'   => $this->airportBasePrice,
        ];
    }
}
