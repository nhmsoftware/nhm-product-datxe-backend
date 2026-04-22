<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\DTO\PricingResultDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\DTO\AcceptRideTrackingDTO;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\DriverCancelRideDTO;
use App\Modules\Ride\DTO\MarkDriverArrivedDTO;
use App\Modules\Ride\DTO\PriceEstimateDTO;
use App\Modules\Ride\DTO\ShowRideTrackingDTO;
use App\Modules\Ride\DTO\UpdateDriverLocationDTO;
use App\Modules\Ride\DTO\VehicleOptionDTO;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\DTO\RequestRideCancellationDTO;
use App\Modules\Driver\DTO\RespondRideCancellationDTO;
use App\Modules\Ride\Events\RideCancellationRequested;
use App\Modules\Ride\Events\RideCancellationResponded;
use App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideTrackingStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\User;

final class RideService extends BaseService implements RideServiceInterface
{
    public function __construct(
        private readonly RideRepositoryInterface $rideRepository,
        private readonly MapServiceInterface $mapService,
        private readonly PricingServiceInterface $pricingService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RideTrackingRealtimeInterface $rideTrackingRealtime
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

            $this->validate(
                $user->is_phone_verified,
                'Vui lòng xác thực số điện thoại để tiếp tục.',
                403
            );

            $matrix = $this->mapService->getDistanceMatrix(
                $dto->pickupLat,
                $dto->pickupLng,
                $dto->destinationLat,
                $dto->destinationLng
            );

            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $matrix->distance,
                durationSeconds: $matrix->duration,
                vehicleType: $dto->vehicleType
            );

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();

            $ride = $this->rideRepository->create([
                'customer_id' => $dto->customerId,
                'pickup_address' => $dto->pickupAddress,
                'pickup_lat' => $dto->pickupLat,
                'pickup_lng' => $dto->pickupLng,
                'destination_address' => $dto->destinationAddress,
                'destination_lat' => $dto->destinationLat,
                'destination_lng' => $dto->destinationLng,
                'distance' => $matrix->distance,
                'duration' => $matrix->duration,
                'vehicle_type' => $dto->vehicleType->value,
                'status' => RideStatus::DRAFT->value,
                'base_price' => $pricingData->baseFare,
                'distance_price' => $pricingData->distanceFare,
                'time_fare' => $pricingData->timeFare,
                'total_price' => $pricingData->finalFare,
                'discount_amount' => 0,
                'is_paid' => false,
            ]);

            return $ride->toArray();
        }, useTransaction: true);
    }

    /**
     * UC-09: Lấy danh sách loại xe kèm giá ước tính
     */
    public function getVehicleOptions(string $rideId, string $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): array {
            $ride = $this->rideRepository->findByIdAndCustomer($rideId, $customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $this->validate(
                $ride->status === RideStatus::DRAFT,
                'Chuyến xe này không thể chọn xe nữa.'
            );

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

                    return VehicleOptionDTO::fromVehicleType($vehicleType, $pricingData->finalFare)->toArray();
                },
                VehicleType::cases()
            )));
        });
    }

    /**
     * UC-10: Xem giá ước tính chi tiết
     */
    public function getPriceEstimate(string $rideId, string $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): array {
            $ride = $this->rideRepository->findByIdAndCustomer($rideId, $customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

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
            $pricingData = $pricingResult->getData();
            $discountAmount = (float) ($ride->discount_amount ?? 0);
            $finalFare = max(0, $pricingData->finalFare - $discountAmount);

            return PriceEstimateDTO::create(
                rideId: $ride->id,
                distanceKm: (float) $ride->distance / 1000,
                durationMinutes: (int) round((float) $ride->duration / 60),
                baseFare: $pricingData->baseFare,
                distanceFare: $pricingData->distanceFare,
                timeFare: $pricingData->timeFare,
                surgeMultiplier: $pricingData->surgeMultiplier,
                originalFare: $pricingData->originalFare,
                finalFare: $finalFare,
                voucherCode: $ride->voucher_code,
                discountAmount: $discountAmount,
            )->toArray();
        });
    }

    /**
     * UC-11: Áp dụng voucher
     */
    public function applyVoucher(ApplyVoucherDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): ServiceReturn {
            $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $this->validate(
                $ride->status === RideStatus::DRAFT,
                'Không thể áp dụng voucher cho chuyến xe này.'
            );

            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();

            $discountAmount = $this->resolveVoucherDiscount($dto->voucherCode, $pricingData->finalFare);
            $this->validate($discountAmount !== null, 'Mã giảm giá không hợp lệ hoặc không thể áp dụng.');

            $finalFare = max(0, $pricingData->finalFare - $discountAmount);

            $this->rideRepository->applyVoucher(
                rideId: $dto->rideId,
                voucherCode: $dto->voucherCode,
                discountAmount: $discountAmount,
                finalPrice: $finalFare
            );

            return $this->getPriceEstimate($dto->rideId, $dto->customerId);
        }, useTransaction: true);
    }

    /**
     * UC-11 A4: Xóa voucher
     */
    public function removeVoucher(string $rideId, string $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): ServiceReturn {
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

            $this->rideRepository->clearVoucher($rideId, $pricingData->finalFare);

            return $this->getPriceEstimate($rideId, $customerId);
        }, useTransaction: true);
    }

    /**
     * UC-12: Xác nhận đặt xe
     */
    public function confirmBooking(ConfirmBookingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): ServiceReturn {
            $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $this->validate(
                $ride->status === RideStatus::DRAFT,
                'Chuyến xe đã được xác nhận hoặc không hợp lệ.'
            );

            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();
            $discountAmount = 0.0;

            if (!empty($ride->voucher_code)) {
                $discountAmount = $this->resolveVoucherDiscount($ride->voucher_code, $pricingData->finalFare);

                if ($discountAmount === null) {
                    $this->rideRepository->clearVoucher($dto->rideId, $pricingData->finalFare);
                    $this->throw('Voucher không còn khả dụng. Giá cước đã thay đổi, vui lòng xác nhận lại.', 409);
                }
            }

            $finalFare = max(0, $pricingData->finalFare - $discountAmount);

            if (abs($finalFare - $dto->expectedPrice) > 1.0) {
                if (!empty($ride->voucher_code)) {
                    $this->rideRepository->applyVoucher($dto->rideId, $ride->voucher_code, $discountAmount, $finalFare);
                } else {
                    $this->rideRepository->clearVoucher($dto->rideId, $finalFare);
                }

                $this->throw('Giá cước đã thay đổi do tình hình giao thông, vui lòng xác nhận lại giá mới.', 409);
            }

            $this->rideRepository->confirmBooking($dto->rideId, $finalFare);

            $ride->refresh();

            return $this->success($ride->toArray(), 'Đặt xe thành công. Đang tìm tài xế.');
        }, useTransaction: true);
    }

    public function showTracking(ShowRideTrackingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $ride = $this->rideRepository->findTrackingRideByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
            $this->validate($ride->driver_id !== null, 'Chuyến đi hiện chưa có tài xế nhận.', 409);

            /** @var User|null $driver */
            $driver = $ride->driver;
            $this->validate($driver !== null && $driver->driverProfile !== null, 'Không tìm thấy thông tin tài xế.', 404);

            return $this->buildTrackingSnapshot($ride, $driver);
        });
    }

    public function acceptTracking(AcceptRideTrackingDTO $dto): ServiceReturn
    {
        $acceptedAt = now();

        return $this->execute(
            function () use ($dto, $acceptedAt): array {
                $ride = $this->rideRepository->findById($dto->rideId);
                $this->validate($ride instanceof Ride, 'Không tìm thấy chuyến xe.', 404);
                $this->validate($ride->status === RideStatus::PENDING, 'Chuyến xe không ở trạng thái chờ nhận.');
                $this->validate($ride->driver_id === null, 'Chuyến xe đã có tài xế nhận.');

                $driver = $this->userRepository->findDriverWithProfileById($dto->driverId);
                $this->validate($driver !== null && $driver->driverProfile !== null, 'Không tìm thấy hồ sơ tài xế.', 404);

                $assigned = $this->rideRepository->assignDriver($dto->rideId, $dto->driverId, $acceptedAt);
                $this->validate($assigned, 'Không thể gán tài xế cho chuyến xe.', 500);

                /** @var Ride|null $trackedRide */
                $trackedRide = $this->rideRepository->findTrackingRideByIdAndDriver($dto->rideId, $dto->driverId);
                $this->validate($trackedRide !== null, 'Không thể khởi tạo tracking cho chuyến xe.', 500);

                return $this->buildTrackingSnapshot($trackedRide, $driver, 'tracking.accepted', 'Tài xế đã nhận chuyến.');
            },
            useTransaction: true,
            afterCommitCallback: function () use ($dto, $acceptedAt): void {
                $this->rideTrackingRealtime->publish([
                    'event' => 'tracking.accepted',
                    'ride_id' => $dto->rideId,
                    'driver_id' => $dto->driverId,
                    'tracking_status' => RideTrackingStatus::DRIVER_ACCEPTED->value,
                    'tracking_status_label' => RideTrackingStatus::DRIVER_ACCEPTED->getLabel(),
                    'occurred_at' => $acceptedAt->toIso8601String(),
                    'message' => 'Tài xế đã nhận chuyến.',
                ]);
            }
        );
    }

    public function updateDriverLocation(UpdateDriverLocationDTO $dto): ServiceReturn
    {
        return $this->execute(
            function () use ($dto): array {
                $ride = $this->rideRepository->findTrackingRideByIdAndDriver($dto->rideId, $dto->driverId);
                $this->validate($ride !== null, 'Không tìm thấy chuyến xe của tài xế.', 404);
                $this->validate($ride->status === RideStatus::ACCEPTED, 'Chuyến xe không ở trạng thái theo dõi.');

                $driver = $this->userRepository->findDriverWithProfileById($dto->driverId);
                $this->validate($driver !== null && $driver->driverProfile !== null, 'Không tìm thấy hồ sơ tài xế.', 404);

                $updatedLocation = $this->userRepository->updateDriverCurrentLocation($dto->driverId, $dto->lat, $dto->lng);
                $this->validate($updatedLocation, 'Không thể cập nhật vị trí tài xế.', 500);

                $heartbeatRefreshed = $this->rideRepository->refreshTrackingHeartbeat($dto->rideId, $dto->trackedAt);
                $this->validate($heartbeatRefreshed, 'Không thể cập nhật trạng thái tài xế.', 500);

                /** @var Ride|null $trackedRide */
                $trackedRide = $this->rideRepository->findTrackingRideByIdAndDriver($dto->rideId, $dto->driverId);
                $this->validate($trackedRide !== null, 'Không thể cập nhật trạng thái tracking.', 500);

                return $this->buildTrackingSnapshot(
                    $trackedRide,
                    $driver,
                    'tracking.location.updated',
                    'Đã cập nhật vị trí tài xế.',
                    [
                        'heading' => $dto->heading,
                        'speed' => $dto->speed,
                        'accuracy' => $dto->accuracy,
                        'tracked_at' => $dto->trackedAt->toIso8601String(),
                    ]
                );
            },
            useTransaction: true,
            afterCommitCallback: function () use ($dto): void {
                $this->rideTrackingRealtime->publish([
                    'event' => 'tracking.location.updated',
                    'ride_id' => $dto->rideId,
                    'driver_id' => $dto->driverId,
                    'location' => [
                        'lat' => $dto->lat,
                        'lng' => $dto->lng,
                        'heading' => $dto->heading,
                        'speed' => $dto->speed,
                        'accuracy' => $dto->accuracy,
                        'tracked_at' => $dto->trackedAt->toIso8601String(),
                    ],
                    'tracking_status' => RideTrackingStatus::DRIVER_EN_ROUTE->value,
                    'tracking_status_label' => RideTrackingStatus::DRIVER_EN_ROUTE->getLabel(),
                    'occurred_at' => $dto->trackedAt->toIso8601String(),
                    'message' => 'Đã cập nhật vị trí tài xế.',
                ]);
            }
        );
    }

    public function markDriverArrived(MarkDriverArrivedDTO $dto): ServiceReturn
    {
        $arrivedAt = now();

        return $this->execute(
            function () use ($dto, $arrivedAt): array {
                $ride = $this->rideRepository->findTrackingRideByIdAndDriver($dto->rideId, $dto->driverId);
                $this->validate($ride !== null, 'Không tìm thấy chuyến xe của tài xế.', 404);
                $this->validate($ride->status === RideStatus::ACCEPTED, 'Không thể cập nhật tài xế đã đến nơi.');

                $driver = $this->userRepository->findDriverWithProfileById($dto->driverId);
                $this->validate($driver !== null && $driver->driverProfile !== null, 'Không tìm thấy hồ sơ tài xế.', 404);

                $arrived = $this->rideRepository->markDriverArrived($dto->rideId, $arrivedAt);
                $this->validate($arrived, 'Không thể cập nhật trạng thái tài xế đã đến nơi.', 500);

                /** @var Ride|null $trackedRide */
                $trackedRide = $this->rideRepository->findTrackingRideByIdAndDriver($dto->rideId, $dto->driverId);
                $this->validate($trackedRide !== null, 'Không thể cập nhật trạng thái tài xế đã đến nơi.', 500);

                return $this->buildTrackingSnapshot($trackedRide, $driver, 'tracking.driver.arrived', 'Tài xế đã đến nơi.');
            },
            useTransaction: true,
            afterCommitCallback: function () use ($dto, $arrivedAt): void {
                $this->rideTrackingRealtime->publish([
                    'event' => 'tracking.driver.arrived',
                    'ride_id' => $dto->rideId,
                    'driver_id' => $dto->driverId,
                    'tracking_status' => RideTrackingStatus::DRIVER_ARRIVED->value,
                    'tracking_status_label' => RideTrackingStatus::DRIVER_ARRIVED->getLabel(),
                    'occurred_at' => $arrivedAt->toIso8601String(),
                    'message' => 'Tài xế đã đến nơi.',
                ]);
            }
        );
    }

    public function cancelByDriver(DriverCancelRideDTO $dto): ServiceReturn
    {
        $cancelledAt = now();

        return $this->execute(
            function () use ($dto): array {
                $ride = $this->rideRepository->findTrackingRideByIdAndDriver($dto->rideId, $dto->driverId);
                $this->validate($ride !== null, 'Không tìm thấy chuyến xe của tài xế.', 404);
                $this->validate($ride->status === RideStatus::ACCEPTED, 'Không thể hủy chuyến ở trạng thái hiện tại.');

                $released = $this->rideRepository->releaseDriverFromRide($dto->rideId, $dto->reason);
                $this->validate($released, 'Không thể đưa chuyến xe về trạng thái tìm tài xế.', 500);

                return [
                    'ride_id' => $dto->rideId,
                    'status' => RideStatus::PENDING->value,
                    'status_label' => RideStatus::PENDING->getLabel(),
                    'message' => 'Tài xế đã hủy chuyến.',
                ];
            },
            useTransaction: true,
            afterCommitCallback: function () use ($dto, $cancelledAt): void {
                $this->rideTrackingRealtime->publish([
                    'event' => 'tracking.driver.cancelled',
                    'ride_id' => $dto->rideId,
                    'driver_id' => $dto->driverId,
                    'tracking_status' => RideTrackingStatus::DRIVER_CANCELLED->value,
                    'tracking_status_label' => RideTrackingStatus::DRIVER_CANCELLED->getLabel(),
                    'occurred_at' => $cancelledAt->toIso8601String(),
                    'message' => 'Tài xế đã hủy chuyến.',
                ]);
            }
        );
    }

    /**
     * UC-15: Hủy chuyến xe
     */
    public function cancelRide(CancelRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $this->validate(
                $ride->status !== RideStatus::IN_PROGRESS,
                'Không thể hủy chuyến khi đã bắt đầu di chuyển.'
            );

            $this->validate(
                !$ride->status->isTerminal(),
                'Chuyến xe này đã hoàn thành hoặc đã bị hủy trước đó.'
            );

            $cancellationFee = 0.0;

            if ($ride->status === RideStatus::ACCEPTED) {
                $cancellationFee = 10000.0;
            }

            $this->rideRepository->cancel($dto->rideId, $dto->reason, $cancellationFee);

            return [
                'ride_id' => $dto->rideId,
                'status' => RideStatus::CANCELLED->getLabel(),
                'cancellation_fee' => $cancellationFee,
            ];
        }, useTransaction: true, afterCommitCallback: function () use ($dto): void {
            $this->rideTrackingRealtime->publish([
                'event' => 'tracking.customer.cancelled',
                'ride_id' => $dto->rideId,
                'customer_id' => $dto->customerId,
                'tracking_status' => RideTrackingStatus::CUSTOMER_CANCELLED->value,
                'tracking_status_label' => RideTrackingStatus::CUSTOMER_CANCELLED->getLabel(),
                'occurred_at' => now()->toIso8601String(),
                'message' => 'Khách hàng đã hủy chuyến.',
            ]);
        });
    }

    private function calculatePriceFor(int $distanceMeters, int $durationSeconds, VehicleType $vehicleType): ServiceReturn
    {
        $pricingRequest = PricingRequestDTO::create(
            distance: $distanceMeters / 1000,
            duration: $durationSeconds / 60,
            vehicleType: $vehicleType->value,
            surgeMultiplier: 1.0
        );

        return $this->pricingService->calculatePrice($pricingRequest);
    }

    private function resolveVoucherDiscount(string $code, float $currentFare): ?float
    {
        if (strlen(trim($code)) < 3) {
            return null;
        }

        $mockVouchers = [
            'DEMO10' => ['discount_amount' => 10000, 'min_fare' => 50000],
            'DEMO50' => ['discount_amount' => 50000, 'min_fare' => 150000],
            'DEMO100' => ['discount_amount' => 100000, 'min_fare' => 300000],
        ];

        $upperCode = strtoupper(trim($code));

        if (!isset($mockVouchers[$upperCode])) {
            $this->throw('Voucher không tồn tại.', 409);
        }

        $voucher = $mockVouchers[$upperCode];

        if ($currentFare < (float) $voucher['min_fare']) {
            $this->throw('Voucher không còn khả dụng. Giá cước không đủ để áp dụng voucher.', 409);
        }

        return (float) $voucher['discount_amount'];
    }

    /**
     * @param array<string, mixed> $extraPayload
     * @return array<string, mixed>
     */
    private function buildTrackingSnapshot(
        Ride $ride,
        User $driver,
        string $event = 'tracking.snapshot',
        string $message = '',
        array $extraPayload = []
    ): array {
        /** @var DriverProfile|null $driverProfile */
        $driverProfile = $driver->driverProfile;

        $hasGps = $driverProfile?->current_lat !== null && $driverProfile?->current_lng !== null;
        $trackedAt = $ride->tracking_last_ping_at;
        $secondsSinceLastPing = $trackedAt?->diffInSeconds(now()) ?? null;
        $isTrackingLost = $secondsSinceLastPing !== null && $secondsSinceLastPing > 45;

        $distanceToPickupMeters = null;
        $etaMinutes = null;

        if ($hasGps) {
            $distanceToPickupMeters = (int) round($this->calculateDistanceMeters(
                (float) $driverProfile->current_lat,
                (float) $driverProfile->current_lng,
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng
            ));

            $etaMinutes = (int) max(1, ceil($distanceToPickupMeters / $this->resolveEtaMetersPerMinute($driverProfile)));
        }

        $trackingStatus = $ride->tracking_status;
        $warning = null;

        if (!$hasGps) {
            $warning = 'Không thể cập nhật vị trí tài xế.';
            $trackingStatus = RideTrackingStatus::TRACKING_LOST;
        } elseif ($isTrackingLost) {
            $warning = 'Không thể cập nhật trạng thái tài xế.';
            $trackingStatus = RideTrackingStatus::TRACKING_LOST;
        }

        return array_merge([
            'event' => $event,
            'message' => $message,
            'ride' => [
                'id' => $ride->id,
                'status' => $ride->status->value,
                'status_label' => $ride->status->getLabel(),
                'tracking_status' => $trackingStatus?->value,
                'tracking_status_label' => $trackingStatus?->getLabel(),
                'pickup_address' => $ride->pickup_address,
                'pickup_lat' => (float) $ride->pickup_lat,
                'pickup_lng' => (float) $ride->pickup_lng,
                'destination_address' => $ride->destination_address,
                'destination_lat' => (float) $ride->destination_lat,
                'destination_lng' => (float) $ride->destination_lng,
                'driver_assigned_at' => $ride->driver_assigned_at?->toIso8601String(),
                'driver_arrived_at' => $ride->driver_arrived_at?->toIso8601String(),
                'tracking_last_ping_at' => $trackedAt?->toIso8601String(),
            ],
            'driver' => [
                'id' => $driver->id,
                'name' => $driverProfile?->full_name ?? $driver->full_name,
                'phone' => $driver->phone,
                'vehicle_number' => $driverProfile?->vehicle_number,
                'vehicle_name' => $driverProfile?->vehicle_name,
                'vehicle_type' => $ride->vehicle_type->value,
                'vehicle_type_label' => $ride->vehicle_type->getLabel(),
                'rating' => $driverProfile?->average_rating !== null ? (float) $driverProfile->average_rating : null,
            ],
            'location' => [
                'lat' => $hasGps ? (float) $driverProfile->current_lat : null,
                'lng' => $hasGps ? (float) $driverProfile->current_lng : null,
                'updated_at' => $trackedAt?->toIso8601String(),
                'is_tracking_lost' => $isTrackingLost,
                'seconds_since_last_ping' => $secondsSinceLastPing,
            ],
            'eta' => [
                'minutes' => $etaMinutes,
                'text' => $etaMinutes !== null ? sprintf('%d phút', $etaMinutes) : null,
                'distance_to_pickup_meters' => $distanceToPickupMeters,
            ],
            'warning' => $warning,
            'realtime' => [
                'room' => sprintf('ride:%d', $ride->id),
                'channel' => 'ride.tracking.events',
            ],
        ], $extraPayload);
    }

    private function resolveEtaMetersPerMinute(?DriverProfile $driverProfile): float
    {
        if ($driverProfile === null) {
            return 350.0;
        }

        return match ($driverProfile->vehicle_type) {
            VehicleType::BIKE->value => 450.0,
            VehicleType::CAR_4_SEATS->value, VehicleType::CAR_7_SEATS->value, VehicleType::CAR_9_SEATS->value => 350.0,
            default => 350.0,
        };
    }

    private function calculateDistanceMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * UC-28: Khách hàng yêu cầu hủy chuyến.
     */
    public function requestCancellation(RequestRideCancellationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            $this->validate(
                !$ride->status->isTerminal(),
                'Chuyến xe này đã hoàn thành hoặc đã bị hủy trước đó.'
            );

            // Nếu đang PENDING (Chờ tài xế), hủy trực tiếp luôn
            if ($ride->status === RideStatus::PENDING) {
                $this->rideRepository->cancel($dto->rideId, $dto->reason, 0);
                return [
                    'ride_id' => $dto->rideId,
                    'status' => RideStatus::CANCELLED->value,
                    'message' => 'Hủy chuyến thành công.',
                ];
            }

            // Nếu đã có tài xế (ACCEPTED/PICKED_UP), chuyển sang trạng thái chờ xác nhận hủy
            if (in_array($ride->status, [RideStatus::ACCEPTED, RideStatus::PICKED_UP], true)) {
                $this->rideRepository->updateStatus($dto->rideId, RideStatus::CANCELLATION_REQUESTED, $dto->reason);

                // Phát sự kiện để Notify Realtime cho Tài xế
                event(new RideCancellationRequested(
                    rideId: $dto->rideId,
                    driverId: (string) $ride->driver_id,
                    customerId: $dto->customerId,
                    reason: $dto->reason
                ));

                return [
                    'ride_id' => $dto->rideId,
                    'status' => RideStatus::CANCELLATION_REQUESTED->value,
                    'message' => 'Yêu cầu hủy đã được gửi tới tài xế.',
                ];
            }

            $this->throw('Không thể yêu cầu hủy chuyến ở trạng thái hiện tại.');
        }, useTransaction: true);
    }

    /**
     * UC-28: Tài xế phản hồi yêu cầu hủy chuyến.
     */
    public function respondToCancellation(RespondRideCancellationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
            $this->validate($ride->status === RideStatus::CANCELLATION_REQUESTED, 'Chuyến xe không ở trạng thái chờ xác nhận hủy.');
            $this->validate((string) $ride->driver_id === $dto->driverId, 'Bạn không phải tài xế của chuyến xe này.', 403);

            if ($dto->isApproved) {
                // Tài xế đồng ý hủy
                $this->rideRepository->cancel($dto->rideId, 'Tài xế chấp nhận yêu cầu hủy từ khách hàng', 0);
            } else {
                // Tài xế không đồng ý, quay lại trạng thái ACCEPTED (hoặc trạng thái trước đó - đơn giản hóa là ACCEPTED)
                $this->rideRepository->updateStatus($dto->rideId, RideStatus::ACCEPTED);
            }

            // Phát sự kiện Notify Realtime cho Khách hàng
            event(new RideCancellationResponded(
                rideId: $dto->rideId,
                customerId: (string) $ride->customer_id,
                driverId: $dto->driverId,
                isApproved: $dto->isApproved
            ));

            return [
                'ride_id' => $dto->rideId,
                'status' => $dto->isApproved ? RideStatus::CANCELLED->value : RideStatus::ACCEPTED->value,
                'is_approved' => $dto->isApproved,
            ];
        }, useTransaction: true);
    }
}
