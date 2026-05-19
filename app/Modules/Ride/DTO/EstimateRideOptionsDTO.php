<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\EstimateRideOptionsRequest;

final readonly class EstimateRideOptionsDTO
{
    public function __construct(
        public string $customerId,
        public float $pickupLat,
        public float $pickupLng,
        public float $destinationLat,
        public float $destinationLng,
    ) {
    }

    public static function fromRequest(EstimateRideOptionsRequest $request): self
    {
        return new self(
            customerId: (string) $request->user()->id,
            pickupLat: (float) $request->input('pickup_lat'),
            pickupLng: (float) $request->input('pickup_lng'),
            destinationLat: (float) $request->input('destination_lat'),
            destinationLng: (float) $request->input('destination_lng'),
        );
    }
}
