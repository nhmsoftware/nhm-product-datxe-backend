<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

/**
 * DTO cho việc phản hồi yêu cầu hủy chuyến (UC-28).
 */
final readonly class RespondRideCancellationDTO
{
    public function __construct(
        public string $rideId,
        public string $driverId,
        public bool $isApproved
    ) {
    }

    /**
     * Factory method từ Request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            driverId: (string) $request->user()->id,
            isApproved: (bool) $request->input('is_approved')
        );
    }
}
