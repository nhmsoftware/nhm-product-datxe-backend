<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;

final class ConfirmBookingDTO
{
    public function __construct(
        public readonly int $rideId,
        public readonly int $customerId,
        public readonly float $expectedPrice,
    ) {
    }

    public static function fromRequest(ConfirmBookingRequest $request, int $rideId): self
    {
        return new self(
            rideId:        $rideId,
            customerId:    (int) $request->user()->id,
            expectedPrice: $request->float('expected_price')
        );
    }
}
