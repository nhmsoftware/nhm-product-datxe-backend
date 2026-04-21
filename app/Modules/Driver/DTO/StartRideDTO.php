<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\StartRideRequest;

/**
 * DTO chứa thông tin để bắt đầu thực hiện chuyến đi.
 */
final readonly class StartRideDTO
{
    public function __construct(
        public string $rideId,
        public string $userId,
        public float $currentLat,
        public float $currentLng,
    ) {
    }

    public static function fromRequest(StartRideRequest $request): self
    {
        return new self(
            rideId: (string) $request->input('rideId'),
            userId: (string) $request->user()->id,
            currentLat: (float) $request->input('lat'),
            currentLng: (float) $request->input('lng')
        );
    }
}
