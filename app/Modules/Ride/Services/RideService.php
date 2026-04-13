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
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\Auth;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\RidePricingDTO;

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
    public function createDraft(CreateDraftRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            /** @var User $user */
            $user = Auth::user();

            // UC-08 Luồng A13: Kiểm tra xác thực số điện thoại
            // Nếu người dùng chưa xác thực số điện thoại, chúng tôi trả về lỗi 403
            // để báo cho client bắt đầu quá trình xác thực OTP.
            if (!$user->is_phone_verified) {
                return ServiceReturn::error(
                    message: 'Vui lòng xác thực số điện thoại để tiếp tục.',
                    data: ['error_code' => 'PHONE_NOT_VERIFIED'],
                    code: 403
                );
            }

            $pickupLat = $dto->pickupLat;
            $pickupLng = $dto->pickupLng;
            $destLat = $dto->destinationLat;
            $destLng = $dto->destinationLng;
            $vehicleType = $dto->vehicleType;

            // 1. Lấy khoảng cách và thời gian di chuyển từ Goong Map Service
            $matrix = $this->mapService->getDistanceMatrix($pickupLat, $pickupLng, $destLat, $destLng);
            $distance = $matrix['distance']; // mét
            $duration = $matrix['duration']; // giây

            // 2. Tính toán giá cước (Logic tính giá dựa trên yêu cầu của UC-10/UC-118)
            $pricing = $this->calculatePrice((int) $distance, $vehicleType);

            // 3. Tạo bản nháp cho chuyến đi
            $ride = $this->rideRepository->create([
                'customer_id' => $user->id,
                'pickup_address' => $dto->pickupAddress,
                'pickup_lat' => $pickupLat,
                'pickup_lng' => $pickupLng,
                'destination_address' => $dto->destinationAddress,
                'destination_lat' => $destLat,
                'destination_lng' => $destLng,
                'distance' => (int) $distance,
                'duration' => (int) $duration,
                'vehicle_type' => $vehicleType->value,
                'status' => RideStatus::DRAFT->value,
                'base_price' => $pricing->basePrice,
                'distance_price' => $pricing->distancePrice,
                'total_price' => $pricing->totalPrice,
                'is_paid' => false,
            ]);

            return $ride->toArray();
        }, useTransaction: true);
    }

    /**
     * Tính toán giá cước ước tính dựa trên khoảng cách và loại xe.
     *
     * Công thức (Tạm thời dựa trên nghiên cứu):
     * - XE MÁY: 12,000 VND (2km đầu tiên), sau đó 4,000 VND mỗi km.
     * - XE 4 CHỖ: 20,000 VND (2km đầu tiên), sau đó 12,000 VND mỗi km.
     * - XE 7 CHỖ: 30,000 VND (2km đầu tiên), sau đó 15,000 VND mỗi km.
     * - XE 9 CHỖ: 40,000 VND (2km đầu tiên), sau đó 18,000 VND mỗi km.
     */
    protected function calculatePrice(int $distanceMeters, VehicleType $vehicleType): RidePricingDTO
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

        return new RidePricingDTO(
            basePrice: (float) $basePrice,
            distancePrice: (float) $distancePrice,
            totalPrice: (float) $totalPrice
        );
    }
}
