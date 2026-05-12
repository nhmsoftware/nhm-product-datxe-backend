<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Chauffeur\DTO\BookChauffeurDTO;
use App\Modules\Chauffeur\Events\ChauffeurBooked;
use App\Modules\Chauffeur\Interfaces\ChauffeurRepositoryInterface;
use App\Modules\Chauffeur\Interfaces\ChauffeurServiceInterface;
use App\Modules\Finance\Interfaces\VoucherServiceInterface;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\User\Interfaces\UserRepositoryInterface;

/**
 * Service xử lý nghiệp vụ Lái hộ.
 */
final class ChauffeurService extends BaseService implements ChauffeurServiceInterface
{
    public function __construct(
        private readonly ChauffeurRepositoryInterface $chauffeurRepository,
        private readonly MapServiceInterface          $mapService,
        private readonly PricingServiceInterface      $pricingService,
        private readonly VoucherServiceInterface      $voucherService,
        private readonly UserRepositoryInterface      $userRepository,
        private readonly \App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface $rideTrackingRealtime
    ) {
    }

    /**
     * @inheritDoc
     */
    public function bookChauffeur(BookChauffeurDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto, &$ride): array {
            // 1. Kiểm tra thông tin khách hàng
            $user = $this->userRepository->findById($dto->customerId);
            $this->validate($user !== null, 'Không tìm thấy thông tin khách hàng.', 404);
            $this->validate($user->is_phone_verified, 'Vui lòng xác thực số điện thoại để tiếp tục.', 403);

            // 2. Tính toán khoảng cách và thời gian từ bản đồ
            $matrix = $this->mapService->getDistanceMatrix(
                $dto->pickupLat,
                $dto->pickupLng,
                $dto->destinationLat,
                $dto->destinationLng
            );

            // 3. Tính giá cước (Sử dụng cấu hình riêng cho Chauffeur)
            $pricingRequest = PricingRequestDTO::create(
                distance:        (float) $matrix->distance / 1000,
                duration:        (float) $matrix->duration / 60,
                vehicleType:     VehicleType::CHAUFFEUR->value,
                surgeMultiplier: 1.0
            );

            $pricingResult = $this->pricingService->calculatePrice($pricingRequest);
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            $pricingData = $pricingResult->getData();
            $totalPrice = (float) $pricingData->finalFare;
            $discountAmount = 0.0;

            // 4. Kiểm tra và áp dụng voucher
            if ($dto->voucherCode) {
                $voucherResult = $this->voucherService->validateAndCalculateDiscount(
                    $dto->customerId,
                    $dto->voucherCode,
                    $totalPrice,
                    'ride'
                );

                if ($voucherResult->isSuccess()) {
                    $discountAmount = (float) $voucherResult->getData();
                    $totalPrice = max(0, $totalPrice - $discountAmount);
                }
            }

            // 5. Khởi tạo chuyến xe với trạng thái PENDING (Đang tìm tài xế)
            $ride = $this->chauffeurRepository->createChauffeurRide([
                'customer_id'             => $dto->customerId,
                'pickup_address'          => $dto->pickupAddress,
                'pickup_lat'              => $dto->pickupLat,
                'pickup_lng'              => $dto->pickupLng,
                'destination_address'     => $dto->destinationAddress,
                'destination_lat'         => $dto->destinationLat,
                'destination_lng'         => $dto->destinationLng,
                'distance'                => $matrix->distance,
                'duration'                => $matrix->duration,
                'vehicle_type'            => VehicleType::CHAUFFEUR->value,
                'ride_type'               => RideType::CHAUFFEUR->value,
                'status'                  => RideStatus::PENDING->value,
                'base_price'              => $pricingData->baseFare,
                'distance_price'          => $pricingData->distanceFare,
                'time_fare'               => $pricingData->timeFare,
                'total_price'             => $totalPrice,
                'voucher_code'            => $dto->voucherCode,
                'discount_amount'         => $discountAmount,
                'is_paid'                 => false,
                'travel_date'             => $dto->pickupTime ? substr($dto->pickupTime, 0, 10) : null,
                'travel_time'             => $dto->pickupTime ? substr($dto->pickupTime, 11, 8) : null,
                'chauffeur_license_plate' => $dto->licensePlate,
                'chauffeur_vehicle_type'  => $dto->carType,
                'chauffeur_brand'         => $dto->carBrand,
                'chauffeur_color'         => $dto->carColor,
            ]);

            // 6. Phát sự kiện Domain Event
            event(new ChauffeurBooked(
                rideId:       (string) $ride->id,
                customerId:   $dto->customerId,
                licensePlate: $dto->licensePlate,
                vehicleType:  $dto->carType,
                brand:        $dto->carBrand,
                color:        $dto->carColor
            ));

            // Phát sự kiện để kích hoạt hệ thống điều phối tài xế (DispatchService)
            event(new \App\Modules\Ride\Events\RideBooked(
                rideId:     (string) $ride->id,
                customerId: $dto->customerId
            ));

            return [
                'ride_id'      => $ride->id,
                'total_price'  => $totalPrice,
                'status_label' => 'Đang tìm tài xế lái hộ...',
            ];
        }, useTransaction: true, afterCommitCallback: function () use (&$ride, $dto): void {
            if ($ride) {
                // Thông báo realtime cho khách hàng về trạng thái chuyến đi
                $this->rideTrackingRealtime->publish([
                    'event'           => 'tracking.chauffeur.booked',
                    'ride_id'         => (string) $ride->id,
                    'customer_id'     => $dto->customerId,
                    'status'          => RideStatus::PENDING->value,
                    'status_label'    => 'Đang tìm tài xế lái hộ...',
                    'occurred_at'     => now()->toIso8601String(),
                    'message'         => 'Yêu cầu lái hộ đã được hệ thống ghi nhận.',
                ]);
            }
        });
    }
}
