<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

final readonly class GetAirportsDTO
{
    public function __construct(
        public ?float $lat = null,
        public ?float $lng = null
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            lat: $request->has('lat') ? (float) $request->input('lat') : null,
            lng: $request->has('lng') ? (float) $request->input('lng') : null,
        );
    }
}
