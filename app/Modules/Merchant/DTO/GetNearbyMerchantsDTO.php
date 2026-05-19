<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use Illuminate\Http\Request;

final class GetNearbyMerchantsDTO
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $radiusInKm = 10.0,
        public readonly ?string $keyword = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude'),
            radiusInKm: (float) $request->input('radius_in_km', 10.0),
            keyword: $request->filled('keyword') ? $request->string('keyword')->trim()->toString() : null,
            page: (int) $request->input('page', 1),
            limit: (int) $request->input('limit', 20),
        );
    }
}
