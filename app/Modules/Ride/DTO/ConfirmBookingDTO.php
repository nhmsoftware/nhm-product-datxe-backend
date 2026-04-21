<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;

final class ConfirmBookingDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $customerId,
        public readonly float $expectedPrice,
    ) {
    }

    public static function fromRequest(ConfirmBookingRequest $request): self
    {
        return new self(
            rideId:        (string) $request->route('rideId'),
            customerId:    (string) $request->user()->id,
            expectedPrice: $request->float('expected_price')
        );
    }
}
