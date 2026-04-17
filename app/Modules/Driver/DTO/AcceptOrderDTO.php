<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\AcceptOrderRequest;

final class AcceptOrderDTO
{
    public function __construct(
        public readonly int $rideId,
        public readonly int $userId,
        public readonly float $currentLat,
        public readonly float $currentLng,
    ) {}

    /**
     * Create DTO from validated FormRequest
     */
    public static function fromRequest(AcceptOrderRequest $request, int $rideId): self
    {
        return new self(
            rideId: $rideId,
            userId: (int) $request->user()->id,
            currentLat: (float) $request->input('current_lat'),
            currentLng: (float) $request->input('current_lng'),
        );
    }
}
