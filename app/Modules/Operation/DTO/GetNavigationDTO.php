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
        public readonly string $rideId,
        public readonly string $userId,
        public readonly int $role,
    ) {
    }

    public static function fromRequest(string $rideId, Request $request): self
    {
        return new self(
            rideId: $rideId,
            userId: (string) $request->user()->id,
            role:   (int) $request->user()->role,
        );
    }
}
