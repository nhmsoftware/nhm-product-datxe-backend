<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\CreateDraftRideRequest;
use App\Modules\Ride\Model\Enums\VehicleType;

/**
 * DTO cho request tạo chuyến xe nháp (UC-08).
 * Factory method fromRequest() nhận trực tiếp FormRequest đã validate.
 */
final class CreateDraftRideDTO
{
    public function __construct(
        public readonly int         $customerId,
        public readonly string      $pickupAddress,
        public readonly float       $pickupLat,
        public readonly float       $pickupLng,
        public readonly string      $destinationAddress,
        public readonly float       $destinationLat,
        public readonly float       $destinationLng,
        public readonly VehicleType $vehicleType,
    ) {
    }

    /**
     * Khởi tạo DTO từ FormRequest đã validate.
     * customerId lấy từ authenticated user — không bao giờ trust client input.
     */
    public static function fromRequest(CreateDraftRideRequest $request): self
    {
        return new self(
            customerId:          (int) $request->user()->id,
            pickupAddress:       $request->string('pickup_address')->toString(),
            pickupLat:           (float) $request->input('pickup_lat'),
            pickupLng:           (float) $request->input('pickup_lng'),
            destinationAddress:  $request->string('destination_address')->toString(),
            destinationLat:      (float) $request->input('destination_lat'),
            destinationLng:      (float) $request->input('destination_lng'),
            vehicleType:         VehicleType::from((int) $request->input('vehicle_type')),
        );
    }
}
