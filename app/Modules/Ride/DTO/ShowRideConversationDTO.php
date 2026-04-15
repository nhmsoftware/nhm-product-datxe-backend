<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\ShowRideConversationRequest;

final class ShowRideConversationDTO
{
    public function __construct(
        public readonly int $rideId,
        public readonly int $actorId,
    ) {
    }

    public static function fromRequest(ShowRideConversationRequest $request, int $rideId): self
    {
        return new self(
            rideId: $rideId,
            actorId: (int) $request->user()->id,
        );
    }
}
