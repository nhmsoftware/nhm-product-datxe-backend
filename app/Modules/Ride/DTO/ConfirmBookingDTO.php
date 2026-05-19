<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;

/**
 * DTO cho UC-12: Xác nhận đặt xe.
 * Khách hàng gửi trực tiếp thông tin chuyến đi để xác nhận đặt xe.
 */
final class ConfirmBookingDTO
{
    public function __construct(
        public readonly string  $customerId,
        public readonly string  $pickupAddress,
        public readonly float   $pickupLat,
        public readonly float   $pickupLng,
        public readonly string  $destinationAddress,
        public readonly float   $destinationLat,
        public readonly float   $destinationLng,
        public readonly int     $vehicleType,
        public readonly float   $expectedPrice,
        public readonly ?string $voucherCode = null,
    ) {}

    public static function fromRequest(ConfirmBookingRequest $request): self
    {
        return new self(
            customerId:         (string) $request->user()->id,
            pickupAddress:      $request->string('pickup_address')->toString(),
            pickupLat:          (float)  $request->input('pickup_lat'),
            pickupLng:          (float)  $request->input('pickup_lng'),
            destinationAddress: $request->string('destination_address')->toString(),
            destinationLat:     (float)  $request->input('destination_lat'),
            destinationLng:     (float)  $request->input('destination_lng'),
            vehicleType:        (int)    $request->input('vehicle_type'),
            expectedPrice:      (float)  $request->input('expected_price'),
            voucherCode:        $request->input('voucher_code') ? $request->string('voucher_code')->toString() : null,
        );
    }
}
