<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\UpdateRideCallStatusRequest;
use App\Modules\Ride\Model\Enums\RideCallStatus;

final class UpdateRideCallStatusDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $callId,
        public readonly string $actorId,
        public readonly RideCallStatus $status,
        public readonly ?string $failureReason,
    ) {
    }

    public static function fromRequest(UpdateRideCallStatusRequest $request, string $rideId, string $callId): self
    {
        return new self(
            rideId: $rideId,
            callId: $callId,
            actorId: (string) $request->user()->id,
            status: RideCallStatus::from((int) $request->input('status')),
            failureReason: $request->input('failure_reason'),
        );
    }
}
