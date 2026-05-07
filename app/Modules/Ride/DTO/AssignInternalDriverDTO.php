<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

final class AssignInternalDriverDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideId: (string) $request->input('ride_id'),
            driverId: (string) $request->input('driver_id')
        );
    }
}
