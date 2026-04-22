<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

/**
 * DTO chứa thông tin tóm tắt chuyến đi phục vụ hiển thị (UC-41).
 */
final readonly class TripSummaryDTO
{
    public function __construct(
        public string $rideId,
        public string $pickupAddress,
        public string $destinationAddress,
        public float $distanceKm,
        public int $durationMin,
        public float $baseFare,
        public float $distanceFare,
        public float $timeFare,
        public float $discountAmount,
        public float $totalCustomerPay,
        public float $serviceFee,
        public float $driverEarnings,
    ) {
    }

    public static function fromModel(\App\Modules\Ride\Model\Ride $ride): self
    {
        return new self(
            rideId: (string) $ride->id,
            pickupAddress: $ride->pickup_address,
            destinationAddress: $ride->destination_address,
            distanceKm: round($ride->distance / 1000, 1),
            durationMin: (int) ceil($ride->duration / 60),
            baseFare: (float) $ride->base_price,
            distanceFare: (float) $ride->distance_price,
            timeFare: (float) ($ride->time_fare ?? 0),
            discountAmount: (float) $ride->discount_amount,
            totalCustomerPay: (float) $ride->total_price,
            serviceFee: (float) $ride->service_fee,
            driverEarnings: (float) $ride->driver_earnings,
        );
    }

    public function toArray(): array
    {
        return [
            'ride_id' => $this->rideId,
            'journey' => [
                'pickup' => $this->pickupAddress,
                'destination' => $this->destinationAddress,
                'distance_km' => $this->distanceKm,
                'duration_min' => $this->durationMin,
            ],
            'fare_details' => [
                'base_fare' => $this->baseFare,
                'distance_fare' => $this->distanceFare,
                'time_fare' => $this->timeFare,
                'discount' => $this->discountAmount,
                'total_fare' => $this->totalCustomerPay,
            ],
            'earnings' => [
                'service_fee' => $this->serviceFee,
                'driver_earnings' => $this->driverEarnings,
            ]
        ];
    }
}
