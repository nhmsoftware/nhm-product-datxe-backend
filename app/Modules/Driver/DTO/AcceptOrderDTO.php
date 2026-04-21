<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\AcceptOrderRequest;

final class AcceptOrderDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $userId,
        public readonly float $currentLat,
        public readonly float $currentLng,
    ) {}

    public static function fromRequest(AcceptOrderRequest $request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            userId: (string) $request->user()->id,
            currentLat: (float) $request->input('current_lat'),
            currentLng: (float) $request->input('current_lng'),
        );
    }
}
