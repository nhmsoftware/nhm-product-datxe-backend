<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

final class BulkPushToPoolDTO
{
    public function __construct(
        public readonly array $rideIds
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideIds: (array) $request->input('ride_ids')
        );
    }
}
