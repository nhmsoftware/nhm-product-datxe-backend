<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Model\Enums\VehicleType;

class CreateDraftRideDTO
{
    public function __construct(
        public readonly string $pickupAddress,
        public readonly float $pickupLat,
        public readonly float $pickupLng,
        public readonly string $destinationAddress,
        public readonly float $destinationLat,
        public readonly float $destinationLng,
        public readonly VehicleType $vehicleType,
    ) {
    }

    /**
     * Khởi tạo DTO từ dữ liệu mảng (ví dụ: đã được validate từ Form Request).
     *
     * @param array $data Dữ liệu đã validate.
     * @return self DTO được khởi tạo
     */
    public static function fromArray(array $data): self
    {
        return new self(
            pickupAddress: $data['pickup_address'],
            pickupLat: (float) $data['pickup_lat'],
            pickupLng: (float) $data['pickup_lng'],
            destinationAddress: $data['destination_address'],
            destinationLat: (float) $data['destination_lat'],
            destinationLng: (float) $data['destination_lng'],
            vehicleType: VehicleType::from((int) $data['vehicle_type'])
        );
    }
}
