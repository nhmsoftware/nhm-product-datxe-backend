<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

final readonly class AcceptRideTrackingDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            driverId: (string) $request->user()->id
        );
    }
}
