<?php

declare(strict_types=1);

namespace App\Modules\Operation\DTO;

use Illuminate\Http\Request;

/**
 * DTO chứa thông tin yêu cầu chỉ đường.
 */
final class GetNavigationDTO
{
    public function __construct(
        public readonly int $rideId,
        public readonly int $userId,
        public readonly int $role,
    ) {
    }

    public static function fromRequest(int $rideId, Request $request): self
    {
        return new self(
            rideId: $rideId,
            userId: (int) $request->user()->id,
            role:   (int) $request->user()->role,
        );
    }
}
