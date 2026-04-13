<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\DTO\PricingResultDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\PriceEstimateDTO;
use App\Modules\Ride\DTO\VehicleOptionDTO;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\User;
use App\Modules\Ride\Interfaces\MapServiceInterface;

final class RideService extends BaseService implements RideServiceInterface
{
    public function __construct(
        private readonly RideRepositoryInterface $rideRepository,
        private readonly MapServiceInterface     $mapService,
        private readonly PricingServiceInterface $pricingService,
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * UC-08: Tạo bản nháp chuyến xe
     */
    public function createDraft(CreateDraftRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            /** @var User|null $user */
            $user = $this->userRepository->findById($dto->customerId);
            $this->validate($user !== null, 'Không tìm thấy thông tin khách hàng.', 404);

            // UC-08 Luồng A13: Kiểm tra xác thực số điện thoại.
            $this->validate(
                $user->is_phone_verified,
                'Vui lòng xác thực số điện thoại để tiếp tục.',
                403
            );

            // 1. Lấy khoảng cách & thời gian từ Goong Map Service
            $matrix   = $this->mapService->getDistanceMatrix(
                $dto->pickupLat,
                $dto->pickupLng,
                $dto->destinationLat,
                $dto->destinationLng
            );

            // 2. Tính giá cước qua Pricing Module
            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $matrix->distance,
                durationSeconds: $matrix->duration,
                vehicleType: $dto->vehicleType
            );

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();

            // 3. Tạo bản nháp chuyến đi trong DB
            $ride = $this->rideRepository->create([
                'customer_id'         => $dto->customerId,
                'pickup_address'      => $dto->pickupAddress,
                'pickup_lat'          => $dto->pickupLat,
                'pickup_lng'          => $dto->pickupLng,
                'destination_address' => $dto->destinationAddress,
                'destination_lat'     => $dto->destinationLat,
                'destination_lng'     => $dto->destinationLng,
                'distance'            => $matrix->distance,
                'duration'            => $matrix->duration,
                'vehicle_type'        => $dto->vehicleType->value,
                'status'              => RideStatus::DRAFT->value,
                'base_price'          => $pricingData->baseFare,
                'distance_price'      => $pricingData->distanceFare,
                'total_price'         => $pricingData->finalFare,
                'discount_amount'     => 0,
                'is_paid'             => false,
            ]);

            return $ride->toArray();
        }, useTransaction: true);
    }

    /**
     * UC-09: Lấy danh sách loại xe kèm giá ước tính
     */
    public function getVehicleOptions(int $rideId, int $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): array {
            // Xác thực quyền sở hữu
            $ride = $this->rideRepository->findByIdAndCustomer($rideId, $customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            // Chỉ hiển thị danh sách xe khi ride đang ở trạng thái Draft
            $this->validate(
                $ride->status === RideStatus::DRAFT,
                'Chuyến xe này không thể chọn xe nữa.'
            );

            // Tính giá ước tính cho từng loại xe
            return array_values(array_filter(array_map(
                function (VehicleType $vehicleType) use ($ride): ?array {
                    $pricingResult = $this->calculatePriceFor(
                        distanceMeters: $ride->distance,
                        durationSeconds: $ride->duration,
                        vehicleType: $vehicleType
                    );

                    if ($pricingResult->isError()) {
                        return null;
                    }

                    /** @var PricingResultDTO $pricingData */
                    $pricingData = $pricingResult->getData();

                    // Dùng Enum helpers và VehicleOptionDTO factory method
                    return VehicleOptionDTO::fromVehicleType($vehicleType, $pricingData->finalFare)->toArray();
                },
                VehicleType::cases()
            )));
        });
    }

    /**
     * UC-10: Xem giá ước tính chi tiết
     */
    public function getPriceEstimate(int $rideId, int $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): array {
            // Xác thực quyền sở hữu chuyến xe
            $ride = $this->rideRepository->findByIdAndCustomer($rideId, $customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            // UC-10 A3: Ride phải có đủ thông tin khoảng cách và thời gian
            $this->validate(
                $ride->distance > 0 && $ride->duration > 0,
                'Không thể xác định thông tin chuyến đi.'
            );

            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData    = $pricingResult->getData();
            $discountAmount = (float) ($ride->discount_amount ?? 0);
            $finalFare      = max(0, $pricingData->finalFare - $discountAmount);

            return PriceEstimateDTO::create(
                rideId:          $ride->id,
                distanceKm:      (float) $ride->distance / 1000,
                durationMinutes: (int) round((float) $ride->duration / 60),
                baseFare:        $pricingData->baseFare,
                distanceFare:    $pricingData->distanceFare,
                timeFare:        $pricingData->timeFare,
                surgeMultiplier: $pricingData->surgeMultiplier,
                originalFare:    $pricingData->originalFare,
                finalFare:       $finalFare,
                voucherCode:     $ride->voucher_code,
                discountAmount:  $discountAmount,
            )->toArray();
        });
    }

    /**
     * UC-11: Áp dụng voucher
     */
    public function applyVoucher(ApplyVoucherDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): ServiceReturn {
            // Xác thực quyền sở hữu chuyến xe
            $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $this->validate(
                $ride->status === RideStatus::DRAFT,
                'Không thể áp dụng voucher cho chuyến xe này.'
            );

            // Validate và lấy giá trị discount
            $discountAmount = $this->resolveVoucherDiscount($dto->voucherCode, $ride);

            // Tính lại giá gốc
            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();
            $finalFare   = max(0, $pricingData->finalFare - $discountAmount);

            // Cập nhật DB qua domain-named method
            $this->rideRepository->applyVoucher(
                rideId:         $dto->rideId,
                voucherCode:    $dto->voucherCode,
                discountAmount: $discountAmount,
                finalPrice:     $finalFare
            );

            return $this->getPriceEstimate($dto->rideId, $dto->customerId);
        }, useTransaction: true);
    }

    /**
     * UC-11 A4: Xóa voucher
     */
    public function removeVoucher(int $rideId, int $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): ServiceReturn {
            // Xác thực quyền sở hữu chuyến xe
            $ride = $this->rideRepository->findByIdAndCustomer($rideId, $customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $this->validate(
                $ride->status === RideStatus::DRAFT,
                'Không thể thay đổi voucher cho chuyến xe này.'
            );

            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();

            // Xóa voucher và khôi phục giá gốc qua domain-named method
            $this->rideRepository->clearVoucher($rideId, $pricingData->finalFare);

            return $this->getPriceEstimate($rideId, $customerId);
        }, useTransaction: true);
    }

    /**
     * Tính giá cước qua PricingService.
     * Private helper để tránh lặp code — chuyển đổi đơn vị và gọi service.
     */
    private function calculatePriceFor(int $distanceMeters, int $durationSeconds, VehicleType $vehicleType): ServiceReturn
    {
        $pricingRequest = PricingRequestDTO::create(
            distance:         $distanceMeters / 1000, // mét → km
            duration:         $durationSeconds / 60,  // giây → phút
            vehicleType:      $vehicleType->value,
            surgeMultiplier:  1.0
        );

        return $this->pricingService->calculatePrice($pricingRequest);
    }

    /**
     * Kiểm tra tính hợp lệ của voucher và trả về số tiền giảm (UC-11).
     * TODO: Khi có VoucherModule, inject VoucherServiceInterface để thay thế mock này.
     */
    private function resolveVoucherDiscount(string $code, Ride $ride): float
    {
        $this->validate(strlen(trim($code)) >= 3, 'Mã giảm giá không hợp lệ.');

        $mockVouchers = [
            'DEMO10'  => ['discount_amount' => 100,  'min_fare' => 300],
            'DEMO50'  => ['discount_amount' => 500,  'min_fare' => 100],
            'DEMO100' => ['discount_amount' => 1000, 'min_fare' => 2000],
        ];

        $upperCode = strtoupper(trim($code));

        $this->validate(isset($mockVouchers[$upperCode]), 'Mã giảm giá không hợp lệ.');

        $voucher = $mockVouchers[$upperCode];
        $this->validate(
            (float) $ride->total_price >= (float) $voucher['min_fare'],
            'Voucher không áp dụng cho chuyến đi này. Giá cước phải lớn hơn hoặc bằng giá tối thiểu của voucher.'
        );

        return (float) $voucher['discount_amount'];
    }
}
