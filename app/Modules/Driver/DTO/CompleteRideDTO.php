<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\CompleteRideRequest;

/**
 * DTO chứa thông tin để hoàn thành chuyến đi.
 */
final readonly class CompleteRideDTO
{
    public function __construct(
        public string $rideId,
        public string $userId,
        public float $currentLat,
        public float $currentLng,
    ) {
    }

    public static function fromRequest(CompleteRideRequest $request): self
    {
        return new self(
            rideId: (string) $request->input('rideId'),
            userId: (string) $request->user()->id,
            currentLat: (float) $request->input('lat'),
            currentLng: (float) $request->input('lng')
        );
    }
}
