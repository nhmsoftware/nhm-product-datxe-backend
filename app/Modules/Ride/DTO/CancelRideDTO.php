<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\CancelRideRequest;

/**
 * DTO chứa thông tin yêu cầu hủy chuyến của khách hàng.
 */
final class CancelRideDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $customerId,
        public readonly ?string $reason = null,
    ) {
    }

    /**
     * Factory method để tạo DTO từ FormRequest.
     * Ở đây FormRequest đã đảm bảo ride_id được validate.
     *
     * @param CancelRideRequest $request
     * @return self
     */
    public static function fromRequest(CancelRideRequest $request): self
    {
        return new self(
            rideId: (string) $request->input('rideId'),
            customerId: (string) $request->user()->id,
            reason: $request->input('reason'),
        );
    }
}
