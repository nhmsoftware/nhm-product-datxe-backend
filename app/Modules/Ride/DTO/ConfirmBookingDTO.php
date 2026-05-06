<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\ConfirmBookingRequest;

/**
 * DTO cho UC-12: Xác nhận đặt xe.
 * Nhận đầy đủ thông tin — tạo draft và xác nhận trong 1 transaction.
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
        /** @var string[] */
        public readonly array   $voucherCodes = [],
        public readonly ?string $note         = null,
    ) {}

    public static function fromRequest(ConfirmBookingRequest $request): self
    {
        return new self(
            customerId:          (string) $request->user()->id,
            pickupAddress:       $request->string('pickup_address')->toString(),
            pickupLat:           (float)  $request->input('pickup_lat'),
            pickupLng:           (float)  $request->input('pickup_lng'),
            destinationAddress:  $request->string('destination_address')->toString(),
            destinationLat:      (float)  $request->input('destination_lat'),
            destinationLng:      (float)  $request->input('destination_lng'),
            vehicleType:         (int)    $request->input('vehicle_type'),
            expectedPrice:       (float)  $request->input('expected_price'),
            voucherCodes:        (array)  $request->input('voucher_codes', []),
            note:                $request->input('note'),
        );
    }
}
