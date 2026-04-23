<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Core\DTO\BaseDTO;
use Illuminate\Http\Request;

final class ShowRideTrackingDTO extends BaseDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $customerId
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            customerId: (string) $request->user()->id
        );
    }
}
