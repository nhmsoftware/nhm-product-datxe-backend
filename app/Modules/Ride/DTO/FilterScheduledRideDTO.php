<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

/**
 * DTO cho việc lọc danh sách chuyến xe đặt trước (UC-47).
 */
final readonly class FilterScheduledRideDTO
{
    public function __construct(
        public string $driverId,
        public ?string $travelDate = null,
        public ?string $travelTime = null,
        public ?string $pickupArea = null,
        public ?string $destinationArea = null,
        public ?int $rideType = null,
        public ?float $minPrice = null,
        public ?float $maxPrice = null
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            driverId: (string) $request->user()->id,
            travelDate: $request->query('travel_date'),
            travelTime: $request->query('travel_time'),
            pickupArea: $request->query('pickup_area'),
            destinationArea: $request->query('destination_area'),
            rideType: $request->query('ride_type') ? (int) $request->query('ride_type') : null,
            minPrice: $request->query('min_price') ? (float) $request->query('min_price') : null,
            maxPrice: $request->query('max_price') ? (float) $request->query('max_price') : null
        );
    }
}
