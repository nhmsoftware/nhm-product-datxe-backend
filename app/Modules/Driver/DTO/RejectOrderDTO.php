<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\RejectOrderRequest;
use App\Modules\Driver\Http\Requests\CancelOrderRequest;
use App\Modules\Ride\Model\Enums\RideCancelReason;

final class RejectOrderDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $userId,
    ) {}

    public static function fromRequest(RejectOrderRequest $request, string $rideId): self
    {
        return new self(
            rideId: $rideId,
            userId: (string) $request->user()->id,
        );
    }
}

// Separate file for CancelOrderDTO if needed, but I'll put it in one go if allowed, 
// however standard is 1 class per file. I'll create them separately.
