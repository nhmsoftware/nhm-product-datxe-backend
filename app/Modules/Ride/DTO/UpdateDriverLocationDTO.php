<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Core\DTO\BaseDTO;
use Illuminate\Http\Request;
use Carbon\CarbonInterface;
use Carbon\Carbon;

final class UpdateDriverLocationDTO extends BaseDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId,
        public readonly float $lat,
        public readonly float $lng,
        public readonly ?float $heading = null,
        public readonly ?float $speed = null,
        public readonly ?float $accuracy = null,
        public readonly CarbonInterface $trackedAt
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            driverId: (string) $request->user()->id,
            lat: (float) $request->input('lat'),
            lng: (float) $request->input('lng'),
            heading: $request->has('heading') ? (float) $request->input('heading') : null,
            speed: $request->has('speed') ? (float) $request->input('speed') : null,
            accuracy: $request->has('accuracy') ? (float) $request->input('accuracy') : null,
            trackedAt: $request->has('tracked_at') ? Carbon::parse($request->input('tracked_at')) : now()
        );
    }
}
