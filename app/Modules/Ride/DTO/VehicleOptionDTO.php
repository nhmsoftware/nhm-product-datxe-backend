<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

/**
 * DTO đại diện cho một loại xe trong danh sách lựa chọn (UC-09).
 * Chứa thông tin hiển thị cho khách hàng.
 */
final class VehicleOptionDTO
{
    public function __construct(
        public readonly int    $vehicleType,
        public readonly string $name,
        public readonly string $description,
        public readonly int    $capacity,
        public readonly float  $estimatedFare,
        public readonly string $estimatedWaitTime,
        public readonly bool   $isAvailable,
    ) {
    }

    public static function create(
        int    $vehicleType,
        string $name,
        string $description,
        int    $capacity,
        float  $estimatedFare,
        string $estimatedWaitTime,
        bool   $isAvailable
    ): self {
        return new self(
            $vehicleType,
            $name,
            $description,
            $capacity,
            $estimatedFare,
            $estimatedWaitTime,
            $isAvailable
        );
    }

    public static function fromMetadata(
        int $vehicleType,
        string $name,
        string $description,
        int $capacity,
        string $estimatedWaitTime,
        float $estimatedFare
    ): self
    {
        return new self(
            vehicleType:       $vehicleType,
            name:              $name,
            description:       $description,
            capacity:          $capacity,
            estimatedFare:     $estimatedFare,
            estimatedWaitTime: $estimatedWaitTime,
            isAvailable:       true,
        );
    }

    public function toArray(): array
    {
        return [
            'vehicle_type_id'     => $this->vehicleType,
            'name'                => $this->name,
            'description'         => $this->description,
            'capacity'            => $this->capacity,
            'estimated_fare'      => $this->estimatedFare,
            'estimated_wait_time' => $this->estimatedWaitTime,
            'is_available'        => $this->isAvailable,
        ];
    }
}
