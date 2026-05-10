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
        public readonly float $intercityDistanceRate,
        public readonly float $intercityTimeRate,
        public readonly float $intercityMinFare,
        public readonly float $airportBasePrice,
        public readonly float $airportDistanceRate,
        public readonly float $airportTimeRate,
        public readonly float $airportMinFare,
        public readonly float $deliveryBasePrice,
        public readonly float $deliveryDistanceRate,
        public readonly float $deliveryTimeRate,
        public readonly float $deliveryMinFare,
        public readonly int $dispatchMode,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            basePrice:             (float) $request->input('base_price'),
            scheduledSurcharge:    (float) $request->input('scheduled_surcharge'),
            intercityBasePrice:    (float) $request->input('intercity_base_price'),
            intercityDistanceRate: (float) $request->input('intercity_distance_rate'),
            intercityTimeRate:     (float) $request->input('intercity_time_rate'),
            intercityMinFare:      (float) $request->input('intercity_min_fare'),
            airportBasePrice:      (float) $request->input('airport_base_price'),
            airportDistanceRate:   (float) $request->input('airport_distance_rate'),
            airportTimeRate:       (float) $request->input('airport_time_rate'),
            airportMinFare:        (float) $request->input('airport_min_fare'),
            deliveryBasePrice:     (float) $request->input('delivery_base_price'),
            deliveryDistanceRate:  (float) $request->input('delivery_distance_rate'),
            deliveryTimeRate:      (float) $request->input('delivery_time_rate'),
            deliveryMinFare:       (float) $request->input('delivery_min_fare'),
            dispatchMode:          (int) $request->input('dispatch_mode'),
        );
    }

    public function toPricingConfigArray(): array
    {
        return [
            'base_price'              => $this->basePrice,
            'scheduled_surcharge'     => $this->scheduledSurcharge,
            'intercity_base_price'    => $this->intercityBasePrice,
            'intercity_distance_rate' => $this->intercityDistanceRate,
            'intercity_time_rate'     => $this->intercityTimeRate,
            'intercity_min_fare'      => $this->intercityMinFare,
            'airport_base_price'      => $this->airportBasePrice,
            'airport_distance_rate'   => $this->airportDistanceRate,
            'airport_time_rate'       => $this->airportTimeRate,
            'airport_min_fare'        => $this->airportMinFare,
            'delivery_base_price'     => $this->deliveryBasePrice,
            'delivery_distance_rate'  => $this->deliveryDistanceRate,
            'delivery_time_rate'      => $this->deliveryTimeRate,
            'delivery_min_fare'       => $this->deliveryMinFare,
        ];
    }
}
