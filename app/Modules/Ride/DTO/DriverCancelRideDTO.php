<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

final readonly class DriverCancelRideDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId,
        public readonly string $reason
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            driverId: (string) $request->user()->id,
            reason: (string) $request->input('reason', 'Tài xế hủy chuyến')
        );
    }
}
