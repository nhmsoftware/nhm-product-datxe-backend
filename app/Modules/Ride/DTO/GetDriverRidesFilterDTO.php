<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

final readonly class GetDriverRidesFilterDTO
{
    public function __construct(
        public string $driverId,
        public ?string $status = null,
        public int $perPage = 15,
        public int $page = 1
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            driverId: (string) $request->user()->id,
            status: $request->query('status'),
            perPage: $request->query('per_page') ? (int) $request->query('per_page') : 15,
            page: $request->query('page') ? (int) $request->query('page') : 1
        );
    }
}
