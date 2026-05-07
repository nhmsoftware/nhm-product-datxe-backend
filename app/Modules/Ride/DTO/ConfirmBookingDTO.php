<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;

/**
 * DTO cho UC-12: Xác nhận đặt xe.
 * Khách hàng chọn loại xe tại bước này để hệ thống tính lại giá chính xác.
 */
final class ConfirmBookingDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $customerId,
        public readonly float  $expectedPrice,
        public readonly int    $vehicleType,
    ) {}

    public static function fromRequest(ConfirmBookingRequest $request): self
    {
        return new self(
            rideId:        (string) $request->route('rideId'),
            customerId:    (string) $request->user()->id,
            expectedPrice: (float)  $request->input('expected_price'),
            vehicleType:   (int)    $request->input('vehicle_type'),
        );
    }
}
