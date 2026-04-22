<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\InitiateRideCallRequest;

final class InitiateRideCallDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $actorId,
    ) {
    }

    public static function fromRequest(InitiateRideCallRequest $request, string $rideId): self
    {
        return new self(
            rideId: $rideId,
            actorId: (string) $request->user()->id,
        );
    }
}
