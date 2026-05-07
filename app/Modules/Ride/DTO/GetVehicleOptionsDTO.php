<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\GetVehicleOptionsRequest;

/**
 * DTO cho UC-09: Lấy danh sách loại xe kèm giá ước tính.
 * Stateless — nhận tọa độ trực tiếp, không cần draft trước.
 */
final class GetVehicleOptionsDTO
{
    public function __construct(
        public readonly string $customerId,
        public readonly float  $pickupLat,
        public readonly float  $pickupLng,
        public readonly float  $destinationLat,
        public readonly float  $destinationLng,
    ) {}

    public static function fromRequest(GetVehicleOptionsRequest $request): self
    {
        return new self(
            customerId:      (string) $request->user()->id,
            pickupLat:       (float)  $request->input('pickup_lat'),
            pickupLng:       (float)  $request->input('pickup_lng'),
            destinationLat:  (float)  $request->input('destination_lat'),
            destinationLng:  (float)  $request->input('destination_lng'),
        );
    }
}
