<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\CancelOrderRequest;
use App\Modules\Ride\Model\Enums\RideCancelReason;

final class CancelOrderDTO
{
    public function __construct(
        public readonly int $rideId,
        public readonly int $userId,
        public readonly RideCancelReason $reason,
        public readonly ?float $currentLat = null,
        public readonly ?float $currentLng = null,
    ) {}

    public static function fromRequest(CancelOrderRequest $request, int $rideId): self
    {
        return new self(
            rideId: $rideId,
            userId: (int) $request->user()->id,
            reason: RideCancelReason::from((int) $request->input('reason_id')),
            currentLat: $request->has('current_lat') ? (float) $request->input('current_lat') : null,
            currentLng: $request->has('current_lng') ? (float) $request->input('current_lng') : null,
        );
    }
}
