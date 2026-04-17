<?php

declare(strict_types=1);

namespace App\Modules\Operation\DTO;

use App\Modules\Operation\Http\Requests\UpdateLocationRequest;

/**
 * DTO chứa tọa độ cập nhật từ User.
 */
final class UpdateLocationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly float $lat,
        public readonly float $lng,
    ) {
    }

    /**
     * Factory method từ FormRequest
     */
    public static function fromRequest(UpdateLocationRequest $request): self
    {
        return new self(
            userId: (int) $request->user()->id,
            lat: (float) $request->input('lat'),
            lng: (float) $request->input('lng'),
        );
    }
}
