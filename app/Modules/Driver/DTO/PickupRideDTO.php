<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\PickupRideRequest;

/**
 * Data Transfer Object cho hành động xác nhận đón khách.
 */
final readonly class PickupRideDTO
{
    public function __construct(
        public string $rideId,
        public string $userId,
        public float $lat,
        public float $lng
    ) {
    }

    /**
     * Tạo DTO từ FormRequest.
     */
    public static function fromRequest(PickupRideRequest $request, string $rideId): self
    {
        return new self(
            rideId: $rideId,
            userId: (string) auth()->id(),
            lat: (float) $request->input('lat'),
            lng: (float) $request->input('lng')
        );
    }
}
