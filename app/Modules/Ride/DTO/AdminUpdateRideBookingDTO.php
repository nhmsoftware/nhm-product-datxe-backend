<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\AdminUpdateRideBookingRequest;

final class AdminUpdateRideBookingDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly int $rideType,
        public readonly string $pickupAddress,
        public readonly ?float $pickupLat,
        public readonly ?float $pickupLng,
        public readonly string $destinationAddress,
        public readonly ?float $destinationLat,
        public readonly ?float $destinationLng,
        public readonly int $vehicleType,
        public readonly float $totalPrice,
        public readonly ?float $distanceKm = null,
        public readonly ?int $durationMinutes = null,
        public readonly ?string $driverId = null,
        public readonly ?string $travelDate = null,
        public readonly ?string $travelTime = null,
        public readonly ?string $airportId = null,
        public readonly ?int $airportDirection = null,
    ) {}

    public static function fromRequest(AdminUpdateRideBookingRequest $request, string $rideId): self
    {
        return new self(
            rideId: $rideId,
            rideType: (int) $request->input('ride_type'),
            pickupAddress: $request->string('pickup_address')->toString(),
            pickupLat: $request->filled('pickup_lat') ? (float) $request->input('pickup_lat') : null,
            pickupLng: $request->filled('pickup_lng') ? (float) $request->input('pickup_lng') : null,
            destinationAddress: $request->string('destination_address')->toString(),
            destinationLat: $request->filled('destination_lat') ? (float) $request->input('destination_lat') : null,
            destinationLng: $request->filled('destination_lng') ? (float) $request->input('destination_lng') : null,
            vehicleType: (int) $request->input('vehicle_type'),
            totalPrice: (float) $request->input('total_price'),
            distanceKm: $request->filled('distance_km') ? (float) $request->input('distance_km') : null,
            durationMinutes: $request->filled('duration_minutes') ? (int) $request->input('duration_minutes') : null,
            driverId: $request->input('driver_id'),
            travelDate: $request->input('travel_date'),
            travelTime: $request->input('travel_time'),
            airportId: $request->input('airport_id'),
            airportDirection: $request->filled('airport_direction') ? (int) $request->input('airport_direction') : null,
        );
    }
}
