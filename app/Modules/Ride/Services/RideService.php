<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use Illuminate\Support\Facades\Auth;

class RideService extends BaseService implements RideServiceInterface
{
    public function __construct(
        protected RideRepositoryInterface $rideRepository,
        protected MapServiceInterface      $mapService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createDraft(array $data): ServiceReturn
    {
        return $this->execute(function () use ($data) {
            /** @var \App\Modules\Auth\Model\User $user */
            $user = Auth::user();

            // UC-08 Flow A13: Phone Verification Check
            // If the user has not verified their phone, we return a 403 error
            // to signal the client to initiate the OTP verification process.
            if (!$user->is_phone_verified) {
                return ServiceReturn::error(
                    message: 'Vui lòng xác thực số điện thoại để tiếp tục.',
                    data: ['error_code' => 'PHONE_NOT_VERIFIED'],
                    code: 403
                );
            }

            $pickupLat = (float) $data['pickup_lat'];
            $pickupLng = (float) $data['pickup_lng'];
            $destLat = (float) $data['destination_lat'];
            $destLng = (float) $data['destination_lng'];
            $vehicleTypeValue = (int) $data['vehicle_type'];
            
            $vehicleType = VehicleType::from($vehicleTypeValue);

            // 1. Get Distance and Duration from Goong Map Service
            $matrix = $this->mapService->getDistanceMatrix($pickupLat, $pickupLng, $destLat, $destLng);
            $distance = $matrix['distance']; // meters
            $duration = $matrix['duration']; // seconds

            // 2. Calculate Pricing (Pricing logic based on UC-10/UC-118 requirements)
            $pricing = $this->calculatePrice($distance, $vehicleType);

            // 3. Create Draft Ride record
            $ride = $this->rideRepository->create([
                'customer_id' => $user->id,
                'pickup_address' => $data['pickup_address'],
                'pickup_lat' => $pickupLat,
                'pickup_lng' => $pickupLng,
                'destination_address' => $data['destination_address'],
                'destination_lat' => $destLat,
                'destination_lng' => $destLng,
                'distance' => (int) $distance,
                'duration' => (int) $duration,
                'vehicle_type' => $vehicleType->value,
                'status' => RideStatus::DRAFT->value,
                'base_price' => $pricing['base_price'],
                'distance_price' => $pricing['distance_price'],
                'total_price' => $pricing['total_price'],
                'is_paid' => false,
            ]);

            return ServiceReturn::success($ride, 'Vị trí đã được ghi nhận. Vui lòng chọn loại xe.');
        }, true);
    }

    /**
     * Calculate estimated price based on distance and vehicle type.
     * 
     * Formula (Placeholder based on research):
     * - BIKE: 12,000 VND (First 2km), then 4,000 VND per km.
     * - CAR_4_SEATS: 20,000 VND (First 2km), then 12,000 VND per km.
     * - CAR_7_SEATS: 30,000 VND (First 2km), then 15,000 VND per km.
     * - CAR_9_SEATS: 40,000 VND (First 2km), then 18,000 VND per km.
     */
    protected function calculatePrice(int $distanceMeters, VehicleType $vehicleType): array
    {
        $distanceKm = $distanceMeters / 1000;
        
        $basePrice = 0;
        $ratePerKm = 0;
        $baseDistance = 2.0;

        switch ($vehicleType) {
            case VehicleType::BIKE:
                $basePrice = 12000;
                $ratePerKm = 4000;
                break;
            case VehicleType::CAR_4_SEATS:
                $basePrice = 20000;
                $ratePerKm = 12000;
                break;
            case VehicleType::CAR_7_SEATS:
                $basePrice = 30000;
                $ratePerKm = 15000;
                break;
            case VehicleType::CAR_9_SEATS:
                $basePrice = 40000;
                $ratePerKm = 18000;
                break;
        }

        $distancePrice = 0;
        if ($distanceKm > $baseDistance) {
            $distancePrice = ($distanceKm - $baseDistance) * $ratePerKm;
        }

        $totalPrice = $basePrice + $distancePrice;

        return [
            'base_price' => (float) $basePrice,
            'distance_price' => (float) $distancePrice,
            'total_price' => (float) $totalPrice,
        ];
    }
}
