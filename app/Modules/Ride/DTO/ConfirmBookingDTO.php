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

    public static function fromRequest(ConfirmBookingRequest $request, string $rideId): self
    {
        return new self(
            rideId:        $rideId,
            customerId:    $request->user()->id->toString(),
            expectedPrice: $request->float('expected_price')
        );
    }
}
