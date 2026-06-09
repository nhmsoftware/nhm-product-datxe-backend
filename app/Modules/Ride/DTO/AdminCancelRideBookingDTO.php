<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

final class AdminCancelRideBookingDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly ?string $reason = null,
    ) {}

    public static function fromRequest(Request $request, string $rideId): self
    {
        return new self(
            rideId: $rideId,
            reason: $request->input('reason'),
        );
    }
}
