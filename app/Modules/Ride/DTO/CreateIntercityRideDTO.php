<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use Illuminate\Http\Request;

/**
 * DTO cho việc đặt xe đi tỉnh (UC-26).
 */
final readonly class CreateIntercityRideDTO
{
    public function __construct(
        public string $customerId,
        public string $pickupAddress,
        public float $pickupLat,
        public float $pickupLng,
        public string $destinationAddress,
        public float $destinationLat,
        public float $destinationLng,
        public string $travelDate,
        public string $travelTime,
        public int $vehicleType,
        public ?string $voucherCode = null
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            customerId: (string) $request->user()->id,
            pickupAddress: $request->input('pickup_address'),
            pickupLat: (float) $request->input('pickup_lat'),
            pickupLng: (float) $request->input('pickup_lng'),
            destinationAddress: $request->input('destination_address'),
            destinationLat: (float) $request->input('destination_lat'),
            destinationLng: (float) $request->input('destination_lng'),
            travelDate: $request->input('travel_date'),
            travelTime: $request->input('travel_time'),
            vehicleType: (int) $request->input('vehicle_type_id', $request->input('vehicle_type')),
            voucherCode: $request->input('voucher_code')
        );
    }
}
