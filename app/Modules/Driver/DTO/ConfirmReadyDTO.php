<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use Illuminate\Http\Request;

/**
 * DTO chứa thông tin xác nhận sẵn sàng nhận chuyến mới.
 */
final readonly class ConfirmReadyDTO
{
    public function __construct(
        public string $rideId,
        public string $userId,
    ) {
    }

    public static function fromRequest(Request $request, string $rideId): self
    {
        return new self(
            rideId: $rideId,
            userId: (string) $request->user()->id,
        );
    }
}
