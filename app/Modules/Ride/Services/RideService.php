<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Core\Helpers\FileHelper;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Driver\Events\RideCancelled;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\DTO\PricingResultDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\DTO\AcceptRideTrackingDTO;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\CreateIntercityRideDTO;
use App\Modules\Ride\DTO\CreateAirportRideDTO;
use App\Modules\Ride\DTO\FilterScheduledRideDTO;
use App\Modules\Ride\DTO\GetDriverRidesFilterDTO;
use App\Modules\Ride\DTO\DriverCancelRideDTO;
use App\Modules\Ride\DTO\EstimateRideOptionsDTO;
use App\Modules\Ride\DTO\MarkDriverArrivedDTO;
use App\Modules\Ride\DTO\PriceEstimateDTO;
use App\Modules\Ride\DTO\ShowRideTrackingDTO;
use App\Modules\Ride\DTO\UpdateDriverLocationDTO;
use App\Modules\Ride\DTO\VehicleOptionDTO;
use App\Modules\Ride\DTO\AssignInternalDriverDTO;
use App\Modules\Ride\DTO\BulkPushToPoolDTO;
use App\Modules\Ride\DTO\CapturePickupProofDTO;
use App\Modules\Ride\DTO\CaptureDeliveryProofDTO;
use App\Modules\Ride\Events\PickupProofCaptured;
use App\Modules\Ride\Events\DeliveryProofCaptured;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\DTO\RequestRideCancellationDTO;
use App\Modules\Ride\Interfaces\AirportRepositoryInterface;
use App\Modules\Driver\DTO\RespondRideCancellationDTO;
use App\Modules\Ride\Events\RideCancellationRequested;
use App\Modules\Ride\Events\RideCancellationResponded;
use App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface;
use App\Modules\Ride\Events\RideBooked;
use App\Modules\Ride\Events\RideAcceptedByDriver;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\RideTrackingStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Ride\Model\Airport;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\DriverStatus;
use App\Modules\User\Model\User;
use App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface;
use App\Modules\RiskManagement\Model\Enums\CancellationFeeType;
use App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface;
use App\Modules\Pricing\Model\Enums\ScheduledDispatchMode;
use App\Modules\Finance\Interfaces\VoucherServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class RideService extends BaseService implements RideServiceInterface
{
    public function __construct(
        private readonly RideRepositoryInterface $rideRepository,
        private readonly MapServiceInterface $mapService,
        private readonly PricingServiceInterface $pricingService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly RideTrackingRealtimeInterface $rideTrackingRealtime,
        private readonly AirportRepositoryInterface $airportRepository,
        private readonly CancellationConfigServiceInterface $cancellationConfigService,
        private readonly PricingGlobalSettingRepositoryInterface $pricingGlobalSettingRepository,
        private readonly VoucherServiceInterface $voucherService
    ) {
    }


    /**
     * UC-09: Lấy danh sách loại xe kèm giá ước tính trước khi confirm.
     */
    public function estimateRideOptions(EstimateRideOptionsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $matrix = $this->mapService->getDistanceMatrix(
                $dto->pickupLat,
                $dto->pickupLng,
                $dto->destinationLat,
                $dto->destinationLng
            );

            $vehicleOptions = array_values(array_filter(array_map(
                function (VehicleType $vehicleType) use ($matrix): ?array {
                    $pricingResult = $this->calculatePriceFor(
                        distanceMeters: $matrix->distance,
                        durationSeconds: $matrix->duration,
                        vehicleType: $vehicleType
                    );

                    if ($pricingResult->isError()) {
                        return null;
                    }

                    /** @var PricingResultDTO $pricingData */
                    $pricingData = $pricingResult->getData();

                    return VehicleOptionDTO::fromVehicleType($vehicleType, $pricingData->finalFare)->toArray();
                },
                [VehicleType::BIKE, VehicleType::CAR_4_SEATS, VehicleType::CAR_7_SEATS, VehicleType::CAR_9_SEATS]
            )));

            return [
                'distance_km' => round($matrix->distance / 1000, 2),
                'duration_minutes' => (int) max(1, round($matrix->duration / 60)),
                'vehicle_options' => $vehicleOptions,
            ];
        });
    }

    /**
     * UC-10: Xem giá ước tính chi tiết
     */
    public function getPriceEstimate(string $rideId, string $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): array {
            if (str_starts_with($rideId, 'draft_')) {
                $draftData = Cache::get('ride_draft:' . $rideId);
                $this->validate($draftData !== null, 'Phiên đặt xe đã hết hạn hoặc không tồn tại.', 404);
                $this->validate((string)$draftData['customer_id'] === (string)$customerId, 'Không tìm thấy chuyến xe.', 404);
                $ride = new Ride($draftData);
                $ride->id = $rideId;
            } else {
                $ride = $this->rideRepository->findByIdAndCustomer($rideId, $customerId);
                $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
            }

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
            if (str_starts_with($dto->rideId, 'draft_')) {
                $draftData = Cache::get('ride_draft:' . $dto->rideId);
                $this->validate($draftData !== null, 'Phiên đặt xe đã hết hạn hoặc không tồn tại.', 404);
                $this->validate((string)$draftData['customer_id'] === (string)$dto->customerId, 'Không tìm thấy chuyến xe.', 404);
                $ride = new Ride($draftData);
                $ride->id = $dto->rideId;
            } else {
                $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
                $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

                $this->validate(
                    $ride->status === RideStatus::DRAFT,
                    'Không thể áp dụng voucher cho chuyến xe này.'
                );
            }

            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();

            $voucherResult = $this->voucherService->validateAndCalculateDiscount($dto->customerId, $dto->voucherCode, $pricingData->finalFare, 'ride');
            if ($voucherResult->isError()) {
                $this->throw($voucherResult->getMessage());
            }

            $discountAmount = (float) $voucherResult->getData();

            $finalFare = max(0, $pricingData->finalFare - $discountAmount);

            if (str_starts_with($dto->rideId, 'draft_')) {
                $draftData['voucher_code'] = $dto->voucherCode;
                $draftData['discount_amount'] = $discountAmount;
                $draftData['total_price'] = $finalFare;
                Cache::put('ride_draft:' . $dto->rideId, $draftData, 1800);
            } else {
                $this->rideRepository->applyVoucher(
                    rideId: $dto->rideId,
                    voucherCode: $dto->voucherCode,
                    discountAmount: $discountAmount,
                    finalPrice: $finalFare
                );
            }

            return $this->getPriceEstimate($dto->rideId, $dto->customerId);
        }, useTransaction: true);
    }

    /**
     * UC-11 A4: Xóa voucher
     */
    public function removeVoucher(string $rideId, string $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): ServiceReturn {
            if (str_starts_with($rideId, 'draft_')) {
                $draftData = Cache::get('ride_draft:' . $rideId);
                $this->validate($draftData !== null, 'Phiên đặt xe đã hết hạn hoặc không tồn tại.', 404);
                $this->validate((string)$draftData['customer_id'] === (string)$customerId, 'Không tìm thấy chuyến xe.', 404);
                $ride = new Ride($draftData);
                $ride->id = $rideId;
            } else {
                $ride = $this->rideRepository->findByIdAndCustomer($rideId, $customerId);
                $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

                $this->validate(
                    $ride->status === RideStatus::DRAFT,
                    'Không thể thay đổi voucher cho chuyến xe này.'
                );
            }

            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();

            if (str_starts_with($rideId, 'draft_')) {
                $draftData['voucher_code'] = null;
                $draftData['discount_amount'] = 0.0;
                $draftData['total_price'] = $pricingData->finalFare;
                Cache::put('ride_draft:' . $rideId, $draftData, 1800);
            } else {
                $this->rideRepository->clearVoucher($rideId, $pricingData->finalFare);
            }

            return $this->getPriceEstimate($rideId, $customerId);
        }, useTransaction: true);
    }

    /**
     * UC-12: Xác nhận đặt xe.
     * Khách hàng truyền trực tiếp thông tin chuyến xe, loại xe đã chọn, giá kỳ vọng, và voucher (nếu có).
     */
    public function confirmBooking(ConfirmBookingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): ServiceReturn {
            /** @var User|null $user */
            $user = $this->userRepository->findById($dto->customerId);
            $this->validate($user !== null, 'Không tìm thấy thông tin khách hàng.', 404);

            $this->validate(
                $user->is_phone_verified,
                'Vui lòng xác thực số điện thoại để tiếp tục.',
                403
            );

            // Gọi map service để tính toán khoảng cách và thời gian di chuyển
            $matrix = $this->mapService->getDistanceMatrix(
                $dto->pickupLat,
                $dto->pickupLng,
                $dto->destinationLat,
                $dto->destinationLng
            );

            $vehicleType = VehicleType::from($dto->vehicleType);

            // Tính toán giá cước
            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $matrix->distance,
                durationSeconds: $matrix->duration,
                vehicleType: $vehicleType
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();
            $discountAmount = 0.0;

            // Xử lý voucher nếu có
            if (!empty($dto->voucherCode)) {
                $voucherResult = $this->voucherService->validateAndCalculateDiscount(
                    $dto->customerId,
                    $dto->voucherCode,
                    $pricingData->finalFare,
                    'ride'
                );

                if ($voucherResult->isError()) {
                    $this->throw('Voucher không còn khả dụng: ' . $voucherResult->getMessage(), 409);
                }

                $discountAmount = (float) $voucherResult->getData();
            }

            $finalFare = max(0, $pricingData->finalFare - $discountAmount);

            // Kiểm tra chênh lệch giá kỳ vọng
            if (abs($finalFare - $dto->expectedPrice) > 1.0) {
                $this->throw('Giá cước đã thay đổi do tình hình giao thông, vui lòng xác nhận lại giá mới.', 409);
            }

            // Tạo chuyến xe trực tiếp vào DB với trạng thái PENDING
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
                'vehicle_type' => $vehicleType->value,
                'status' => RideStatus::PENDING->value,
                'base_price' => $pricingData->baseFare,
                'distance_price' => $pricingData->distanceFare,
                'time_fare' => $pricingData->timeFare,
                'total_price' => $finalFare,
                'discount_amount' => $discountAmount,
                'voucher_code' => $dto->voucherCode,
                'is_paid' => false,
            ]);

            // Phát sự kiện RideBooked để DispatchService bắt đầu tìm tài xế
            event(new RideBooked(rideId: $ride->id, customerId: $dto->customerId));

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

            // Tính toán thời gian còn lại đến khi đón khách (cho chuyến đặt trước)
            $minutesUntilPickup = 999999; // Mặc định rất lớn cho chuyến nội thành (City)
            if ($ride->travel_date && $ride->travel_time) {
                $pickupDateTime = \Illuminate\Support\Carbon::parse(
                    $ride->travel_date->format('Y-m-d') . ' ' . $ride->travel_time
                );
                $minutesUntilPickup = (int) now()->diffInMinutes($pickupDateTime, false);
            }

            // Lấy quy tắc phí hủy từ cấu hình
            $feeResult = $this->cancellationConfigService->getApplicableFee($ride->ride_type->value, $minutesUntilPickup);

            if ($feeResult->isSuccess()) {
                $feeData = $feeResult->getData();
                if ($feeData['fee_type'] === CancellationFeeType::FIXED->value) {
                    $cancellationFee = (float) $feeData['fee_value'];
                } elseif ($feeData['fee_type'] === CancellationFeeType::PERCENTAGE->value) {
                    $cancellationFee = ($ride->total_price * (float) $feeData['fee_value']) / 100;
                }
            }

            // Fallback: Nếu là chuyến nội thành đã có tài xế và chưa có cấu hình riêng, áp dụng phí mặc định 10k
            if ($ride->ride_type === RideType::CITY && $ride->status === RideStatus::ACCEPTED && $cancellationFee <= 0) {
                $cancellationFee = 10000.0;
            }

            if ($ride->status === RideStatus::ACCEPTED) {
                // Cảnh báo gian lận
                $driverProfile = $this->driverProfileRepository->findByUserId((string) $ride->driver_id);
                if ($driverProfile) {
                    $this->checkFraudProximity($ride, $driverProfile, 'General cancellation');
                }
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
                'ride_id' => (string) $dto->rideId,
                'customer_id' => (string) $dto->customerId,
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

    private function resolveVoucherDiscount(string $customerId, string $code, float $currentFare): ?float
    {
        $result = $this->voucherService->validateAndCalculateDiscount($customerId, $code, $currentFare, 'ride');
        if ($result->isError()) {
            return null;
        }

        return (float) $result->getData();
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
                'id' => (string) $ride->id,
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
                'id' => (string) $driver->id,
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
                'room' => sprintf('ride:%s', (string) $ride->id),
                'channel' => 'ride.tracking.events',
            ],
        ], $extraPayload);
    }

    private function resolveEtaMetersPerMinute(?DriverProfile $driverProfile): float
    {
        if ($driverProfile === null) {
            return 350.0;
        }

        return match ($driverProfile->vehicle_type->value) {
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
                // Cảnh báo gian lận: Kiểm tra khoảng cách tài xế và điểm đón khi hủy
                $driverProfile = $this->driverProfileRepository->findByUserId((string) $ride->driver_id);
                if ($driverProfile) {
                    $this->checkFraudProximity($ride, $driverProfile, 'Customer requested cancellation');
                }

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

    /**
     * Kiểm tra gian lận: Tài xế ở gần điểm đón nhưng khách hủy/hủy chuyến
     * (Nghi ngờ tài xế yêu cầu khách hủy để chạy ngoài)
     */
    private function checkFraudProximity(Ride $ride, $driverProfile, string $context): void
    {
        if (!$driverProfile || $driverProfile->current_lat === null) {
            return;
        }

        $distance = $this->calculateDistanceMeters(
            (float) $driverProfile->current_lat,
            (float) $driverProfile->current_lng,
            (float) $ride->pickup_lat,
            (float) $ride->pickup_lng
        );

        // Nếu tài xế ở trong phạm vi 200m hoặc đã báo đến nơi (driver_arrived_at)
        $isArrived = $ride->driver_arrived_at !== null;

        if ($distance < 200 || $isArrived) {
            Log::warning(sprintf(
                "[FRAUD_ALERT] Nghi vấn gian lận (offline ride): Chuyến xe %s bị hủy (%s). Khoảng cách tài xế - điểm đón: %.2fm. Trạng thái đã đến: %s. Driver ID: %s",
                $ride->id,
                $context,
                $distance,
                $isArrived ? 'Có' : 'Không',
                $driverProfile->user_id
            ));
        }
    }

    public function createIntercity(CreateIntercityRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Tính toán khoảng cách và thời gian thực tế từ MapService
            $matrix = $this->mapService->getDistanceMatrix(
                (float) $dto->pickupLat,
                (float) $dto->pickupLng,
                (float) $dto->destinationLat,
                (float) $dto->destinationLng
            );
            $distanceMeters = $matrix->distance;
            $durationSeconds = $matrix->duration;

            // 2. Tính giá cước
            $vehicleType = VehicleType::from($dto->vehicleType);
            $pricingResult = $this->calculatePriceFor($distanceMeters, $durationSeconds, $vehicleType);
            if ($pricingResult->isError()) {
                $this->throw($pricingResult->getMessage());
            }

            $priceData = $pricingResult->getData();
            $totalPrice = (float) $priceData->finalFare;
            $discountAmount = 0.0;
            $voucherId = null;

            // 3. Áp dụng voucher nếu có
            if ($dto->voucherCode) {
                $discount = $this->resolveVoucherDiscount($dto->customerId, $dto->voucherCode, $totalPrice);
                if ($discount !== null) {
                    $discountAmount = $discount;
                    $totalPrice = max(0, $totalPrice - $discountAmount);
                }
            }

            // 4. Tạo chuyến xe với RideType::INTERCITY
            $ride = $this->rideRepository->createIntercityRide([
                'customer_id'         => $dto->customerId,
                'pickup_address'      => $dto->pickupAddress,
                'pickup_lat'          => $dto->pickupLat,
                'pickup_lng'          => $dto->pickupLng,
                'destination_address' => $dto->destinationAddress,
                'destination_lat'     => $dto->destinationLat,
                'destination_lng'     => $dto->destinationLng,
                'distance'            => $distanceMeters,
                'duration'            => $durationSeconds,
                'vehicle_type'        => $dto->vehicleType,
                'ride_type'           => RideType::INTERCITY->value,
                'travel_date'         => $dto->travelDate,
                'travel_time'         => $dto->travelTime,
                'status'              => RideStatus::PENDING->value,
                'base_price'          => $priceData->baseFare,
                'distance_price'      => $priceData->distanceFare,
                'total_price'         => $totalPrice,
                'voucher_code'        => $dto->voucherCode,
                'discount_amount'     => $discountAmount,
                'is_pushed_to_pool'   => $this->shouldPushToPoolImmediately(),
            ]);

            // 5. Không phát sự kiện RideBooked ngay lập tức để tránh tự động dispatch (theo yêu cầu)
            // Chuyến đi sẽ nằm trong Pool (Scheduled Rides) để tài xế tự chọn.
            // event(new RideBooked($ride->id, $ride->customer_id));

            return [
                'ride_id'      => $ride->id,
                'ride_type'    => 'intercity',
                'total_price'  => $totalPrice,
                'status_label' => 'Đã lưu lịch trình. Chuyến đi sẽ hiển thị cho tài xế nhận sớm.',
            ];
        }, true);
    }

    public function createAirport(CreateAirportRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Tính toán khoảng cách và thời gian thực tế từ MapService
            $matrix = $this->mapService->getDistanceMatrix(
                (float) $dto->pickupLat,
                (float) $dto->pickupLng,
                (float) $dto->destinationLat,
                (float) $dto->destinationLng
            );
            $distanceMeters = $matrix->distance;
            $durationSeconds = $matrix->duration;

            // 2. Tính giá cước
            $vehicleType = VehicleType::from($dto->vehicleType);
            $pricingResult = $this->calculatePriceFor($distanceMeters, $durationSeconds, $vehicleType);
            if ($pricingResult->isError()) {
                $this->throw($pricingResult->getMessage());
            }

            $priceData = $pricingResult->getData();
            $totalPrice = (float) $priceData->finalFare;
            $discountAmount = 0.0;

            // 3. Áp dụng voucher nếu có
            if ($dto->voucherCode) {
                $discount = $this->resolveVoucherDiscount($dto->customerId, $dto->voucherCode, $totalPrice);
                if ($discount !== null) {
                    $discountAmount = $discount;
                    $totalPrice = max(0, $totalPrice - $discountAmount);
                }
            }

            // 4. Tạo chuyến xe với RideType::AIRPORT
            $ride = $this->rideRepository->createAirportRide([
                'customer_id'         => $dto->customerId,
                'pickup_address'      => $dto->pickupAddress,
                'pickup_lat'          => $dto->pickupLat,
                'pickup_lng'          => $dto->pickupLng,
                'destination_address' => $dto->destinationAddress,
                'destination_lat'     => $dto->destinationLat,
                'destination_lng'     => $dto->destinationLng,
                'distance'            => $distanceMeters,
                'duration'            => $durationSeconds,
                'vehicle_type'        => $dto->vehicleType,
                'ride_type'           => RideType::AIRPORT->value,
                'travel_date'         => $dto->travelDate,
                'travel_time'         => $dto->travelTime,
                'airport_id'          => $dto->airportId,
                'airport_direction'   => $dto->airportDirection,
                'status'              => RideStatus::PENDING->value,
                'base_price'          => $priceData->baseFare,
                'distance_price'      => $priceData->distanceFare,
                'total_price'         => $totalPrice,
                'voucher_code'        => $dto->voucherCode,
                'discount_amount'     => $discountAmount,
                'is_pushed_to_pool'   => $this->shouldPushToPoolImmediately(),
            ]);

            // 5. Không phát sự kiện RideBooked ngay lập tức để tránh tự động dispatch (theo yêu cầu)
            // Chuyến đi sẽ nằm trong Pool (Scheduled Rides) để tài xế tự chọn.
            // event(new RideBooked($ride->id, $ride->customer_id));

            return [
                'ride_id'      => $ride->id,
                'ride_type'    => 'airport',
                'total_price'  => $totalPrice,
                'status_label' => 'Đã lưu lịch trình. Chuyến đi sẽ hiển thị cho tài xế nhận sớm.',
            ];
        }, true);
    }

    /**
     * UC-25: Tạo đơn giao hàng.
     *
     * Luồng:
     * 1. Tính khoảng cách thực tế qua MapService
     * 2. Tính giá cước theo loại xe
     * 3. Áp dụng voucher (nếu có)
     * 4. Tạo Ride với ride_type = DELIVERY
     * 5. Tạo DeliveryOrder đính kèm thông tin người gửi/nhận/hàng hóa
     * 6. Phát Event RideBooked để hệ thống tìm tài xế
     */
    public function createDeliveryOrder(\App\Modules\Ride\DTO\CreateDeliveryOrderDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Kiểm tra điểm lấy và điểm giao không được trùng (A2)
            $this->validate(
                !($dto->pickupLat === $dto->destinationLat && $dto->pickupLng === $dto->destinationLng),
                'Điểm lấy và điểm giao không được trùng nhau.',
                422
            );

            // 2. Tính khoảng cách & thời gian thực tế
            $matrix = $this->mapService->getDistanceMatrix(
                $dto->pickupLat,
                $dto->pickupLng,
                $dto->destinationLat,
                $dto->destinationLng
            );
            $distanceMeters  = $matrix->distance;
            $durationSeconds = $matrix->duration;

            // 3. Tính giá cước theo loại xe
            $vehicleType   = VehicleType::from($dto->vehicleType);
            $pricingResult = $this->calculatePriceFor($distanceMeters, $durationSeconds, $vehicleType);
            if ($pricingResult->isError()) {
                $this->throw($pricingResult->getMessage());
            }

            $priceData      = $pricingResult->getData();
            $totalPrice     = (float) $priceData->finalFare;
            $discountAmount = 0.0;

            // 4. Áp dụng voucher (nếu có)
            if ($dto->voucherCode) {
                $discount = $this->resolveVoucherDiscount($dto->customerId, $dto->voucherCode, $totalPrice);
                if ($discount !== null) {
                    $discountAmount = $discount;
                    $totalPrice     = max(0, $totalPrice - $discountAmount);
                }
            }

            // 5. Tạo Ride với RideType::DELIVERY
            $ride = $this->rideRepository->createDeliveryRide([
                'customer_id'         => $dto->customerId,
                'pickup_address'      => $dto->pickupAddress,
                'pickup_lat'          => $dto->pickupLat,
                'pickup_lng'          => $dto->pickupLng,
                'destination_address' => $dto->destinationAddress,
                'destination_lat'     => $dto->destinationLat,
                'destination_lng'     => $dto->destinationLng,
                'distance'            => $distanceMeters,
                'duration'            => $durationSeconds,
                'vehicle_type'        => $dto->vehicleType,
                'ride_type'           => RideType::DELIVERY->value,
                'status'              => RideStatus::PENDING->value,
                'base_price'          => $priceData->baseFare,
                'distance_price'      => $priceData->distanceFare,
                'total_price'         => $totalPrice,
                'voucher_code'        => $dto->voucherCode,
                'discount_amount'     => $discountAmount,
            ]);

            // 6. Tạo DeliveryOrder đính kèm
            $this->rideRepository->createDeliveryOrderDetail([
                'ride_id'        => $ride->id,
                'sender_name'    => $dto->senderName,
                'sender_phone'   => $dto->senderPhone,
                'receiver_name'  => $dto->receiverName,
                'receiver_phone' => $dto->receiverPhone,
                'goods_type'     => $dto->goodsType,
                'goods_weight'   => $dto->goodsWeight,
                'goods_note'     => $dto->goodsNote,
                'is_fragile'     => $dto->isFragile,
            ]);

            // 7. Phát Event để hệ thống bắt đầu tìm tài xế
            event(new RideBooked($ride->id, $ride->customer_id));

            return [
                'ride_id'        => $ride->id,
                'ride_type'      => 'delivery',
                'total_price'    => $totalPrice,
                'distance_km'    => round($distanceMeters / 1000, 2),
                'duration_min'   => round($durationSeconds / 60),
                'status'         => 'pending',
                'status_label'   => 'Đang tìm tài xế giao hàng.',
                'receiver_name'  => $dto->receiverName,
                'receiver_phone' => $dto->receiverPhone,
            ];
        }, true);
    }

    public function getRideDetail(string $rideId, string $customerId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $customerId): array {
            $ride = $this->rideRepository->findWithDriverDetail($rideId, $customerId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);

            $data = $ride->toArray();

            // Bổ sung label cho Enums
            $data['status_label'] = $ride->status->getLabel();
            $data['vehicle_type_label'] = $ride->vehicle_type->getLabel();
            $data['ride_type_label'] = $ride->ride_type->getLabel();

            // Thông tin tài xế nếu đã có
            if ($ride->driver && $ride->driver->driverProfile) {
                $profile = $ride->driver->driverProfile;
                $data['driver_info'] = [
                    'full_name'      => $profile->full_name,
                    'phone'          => $ride->driver->phone,
                    'avatar'         => $ride->driver->avatar,
                    'vehicle_number' => $profile->vehicle_number,
                    'vehicle_name'   => $profile->vehicle_name,
                    'average_rating' => (float) $profile->average_rating,
                    'total_trips'    => $profile->total_trips,
                ];
            } else {
                $data['driver_info'] = null;
            }

            return $data;
        });
    }

    public function getAvailableScheduledRides(FilterScheduledRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Lấy thông tin driver để biết vehicle_type
            $driver = $this->userRepository->findDriverWithProfileById($dto->driverId);
            $this->validate($driver !== null, 'Không tìm thấy thông tin tài xế.', 404);
            $this->validate($driver->driverProfile !== null, 'Bạn chưa đăng ký loại xe để nhận chuyến.', 400);

            // 2. Lấy danh sách từ repository
            $filters = (array) $dto;
            $rides = $this->rideRepository->findAvailableScheduledRides(
                $driver->driverProfile->vehicle_type->value,
                $filters
            );

            // 3. Format kết quả trả về
            return $rides->map(function ($ride) {
                return [
                    'ride_id'             => $ride->id,
                    'pickup_address'      => $ride->pickup_address,
                    'destination_address' => $ride->destination_address,
                    'travel_date'         => $ride->travel_date ? $ride->travel_date->format('Y-m-d') : null,
                    'travel_time'         => $ride->travel_time,
                    'vehicle_type'        => $ride->vehicle_type->value,
                    'vehicle_type_label'  => $ride->vehicle_type->getLabel(),
                    'ride_type'           => $ride->ride_type->value,
                    'ride_type_label'     => $ride->ride_type->getLabel(),
                    'total_price'         => (float) $ride->total_price,
                    'expected_earnings'   => (float) ($ride->total_price * 0.8), // Giả sử driver nhận 80%
                ];
            })->toArray();
        });
    }

    public function getScheduledRideDetail(string $rideId, string $driverId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $driverId): array {
            // 1. Lấy thông tin driver để biết vehicle_type
            $driver = $this->userRepository->findDriverWithProfileById($driverId);
            $this->validate($driver !== null, 'Không tìm thấy thông tin tài xế.', 404);
            $this->validate($driver->driverProfile !== null, 'Bạn chưa đăng ký loại xe.', 400);

            // 2. Lấy thông tin chuyến xe
            $ride = $this->rideRepository->findAvailableById($rideId);
            if ($ride === null) {
                // Kiểm tra xem có phải do đã được nhận/hủy không
                $anyRide = $this->rideRepository->findById($rideId);
                $this->validate($anyRide !== null, 'Chuyến xe không tồn tại.', 404);
                $this->throw('Chuyến xe không còn khả dụng.', 400);
            }

            // 3. Kiểm tra phù hợp loại xe
            $this->validate(
                $ride->vehicle_type->value === $driver->driverProfile->vehicle_type->value,
                'Bạn không đủ điều kiện để xem chuyến xe này.',
                403
            );

            // 4. Trả về chi tiết
            return [
                'ride_id'             => $ride->id,
                'pickup_address'      => $ride->pickup_address,
                'pickup_lat'          => (float) $ride->pickup_lat,
                'pickup_lng'          => (float) $ride->pickup_lng,
                'destination_address' => $ride->destination_address,
                'destination_lat'     => (float) $ride->destination_lat,
                'destination_lng'     => (float) $ride->destination_lng,
                'distance'            => $ride->distance,
                'duration'            => $ride->duration,
                'travel_date'         => $ride->travel_date ? $ride->travel_date->format('Y-m-d') : null,
                'travel_time'         => $ride->travel_time,
                'vehicle_type'        => $ride->vehicle_type->value,
                'vehicle_type_label'  => $ride->vehicle_type->getLabel(),
                'ride_type'           => $ride->ride_type->value,
                'ride_type_label'     => $ride->ride_type->getLabel(),
                'total_price'         => (float) $ride->total_price,
                'expected_earnings'   => (float) ($ride->total_price * 0.8),
                'note'                => $ride->note ?? '',
                'status'              => $ride->status->value,
                'status_label'        => $ride->status->getLabel(),
            ];
        });
    }

    public function acceptScheduledRide(string $rideId, string $driverId): ServiceReturn
    {
        return $this->execute(function () use ($rideId, $driverId): array {
            // 1. Lấy thông tin driver
            $driver = $this->userRepository->findDriverWithProfileById($driverId);
            $this->validate($driver !== null, 'Không tìm thấy thông tin tài xế.', 404);
            $this->validate($driver->driverProfile !== null, 'Bạn chưa đăng ký loại xe.', 400);

            // 2. Lấy thông tin chuyến xe
            $ride = $this->rideRepository->findById($rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);

            // 3. Kiểm tra trạng thái và loại xe
            $this->validate($ride->status === RideStatus::PENDING, 'Chuyến xe đã được tài xế khác nhận hoặc không còn khả dụng.', 400);
            $this->validate(
                $ride->vehicle_type->value === $driver->driverProfile->vehicle_type->value,
                'Loại xe của bạn không phù hợp với chuyến này.',
                403
            );

            // 4. Gán tài xế (Atomic update)
            $success = $this->rideRepository->assignDriver($rideId, $driverId, now());
            $this->validate($success, 'Không thể nhận chuyến. Có thể chuyến xe vừa được người khác nhận.', 400);

            // 5. Phát sự kiện để Notify Realtime cho Khách hàng
            event(new RideAcceptedByDriver(
                rideId: $rideId,
                driverId: $driverId,
                customerId: (string) $ride->customer_id
            ));

            return [
                'ride_id' => $rideId,
                'status'  => RideStatus::ACCEPTED->value,
                'message' => 'Bạn đã nhận chuyến thành công.'
            ];
        }, useTransaction: true);
    }

    public function driverCancelScheduledRide(DriverCancelRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Lấy thông tin chuyến xe
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Chuyến xe không tồn tại.', 404);
            $this->validate((string) $ride->driver_id === $dto->driverId, 'Bạn không có quyền hủy chuyến xe này.', 403);

            // 2. Kiểm tra trạng thái (Chỉ được hủy khi đã nhận nhưng chưa bắt đầu)
            $this->validate(
                $ride->status === RideStatus::ACCEPTED,
                'Không thể hủy chuyến ở trạng thái hiện tại.'
            );

            // 3. Kiểm tra thời gian cho phép tự hủy (Giả sử 30 phút theo cấu hình Admin)
            $maxCancelMinutes = 30;
            if ($ride->driver_assigned_at) {
                $minutesSinceAccepted = now()->diffInMinutes($ride->driver_assigned_at);
                if ($minutesSinceAccepted > $maxCancelMinutes) {
                    $this->throw('Bạn không thể tự hủy chuyến sau ' . $maxCancelMinutes . ' phút nhận đơn. Vui lòng liên hệ khách hàng.', 400);
                }
            }

            // 4. Thực hiện giải phóng tài xế khỏi chuyến xe đặt trước
            $success = $this->rideRepository->releaseDriverFromRide($ride->id, $dto->reason);
            $this->validate($success, 'Không thể giải phóng tài xế. Vui lòng thử lại.', 400);

            // Ghi nhận vào danh sách từ chối để không hiển thị lại cho tài xế này (Anti-re-acceptance)
            $this->rideRepository->rejectByDriver($ride->id, $dto->driverId);

            // Tính toán hình phạt (Penalty System)
            $driverProfile = $this->driverProfileRepository->findByUserId($dto->driverId);
            $this->validate($driverProfile !== null, 'Hồ sơ tài xế không tồn tại.', 404);

            $penaltyMinutes = 0;
            // Kiểm tra số lần hủy trong ngày (lấy từ cancel_count_today trên profile tài xế và cộng thêm 1)
            $newCancelCount = ($driverProfile->cancel_count_today ?? 0) + 1;
            $this->driverProfileRepository->updateCancelCount($driverProfile->id, $newCancelCount);

            if ($newCancelCount >= 3) {
                $penaltyMinutes = 60; // Hình phạt 60 phút do hủy quá nhiều
            }

            // Áp dụng trạng thái nghỉ (Cooldown) nếu có phạt
            if ($penaltyMinutes > 0) {
                $this->driverProfileRepository->setCooldown($driverProfile->id, $penaltyMinutes);
            } else {
                // Đưa tài xế trở lại trạng thái sẵn sàng (ACTIVE)
                $this->driverProfileRepository->updateStatus($driverProfile->id, DriverStatus::ACTIVE);
            }

            // 5. Phát sự kiện để Notify Realtime cho Khách hàng (Sử dụng sự kiện RideCancelled của Driver để biểu diễn tài xế hủy)
            event(new RideCancelled(
                rideId: $dto->rideId,
                driverId: (string) $dto->driverId,
                reason: $dto->reason
            ));

            // Publish tracking update để báo cho Client
            $this->rideTrackingRealtime->publish([
                'event' => 'tracking.driver.cancelled',
                'ride_id' => (string) $ride->id,
                'customer_id' => (string) $ride->customer_id,
                'tracking_status' => RideTrackingStatus::DRIVER_CANCELLED->value,
                'tracking_status_label' => RideTrackingStatus::DRIVER_CANCELLED->getLabel(),
                'occurred_at' => now()->toIso8601String(),
                'message' => 'Tài xế đã hủy chuyến. Hệ thống đang tìm tài xế khác.',
            ]);

            return [
                'ride_id' => $dto->rideId,
                'status'  => RideStatus::PENDING->value,
                'message' => 'Hủy chuyến thành công. Đơn hàng đang tìm tài xế khác.'
            ];
        }, useTransaction: true);
    }

    public function getDriverManagedRides(string $driverId): ServiceReturn
    {
        return $this->execute(function () use ($driverId): array {
            $rides = $this->rideRepository->findDriverAcceptedRides($driverId);

            return $rides->map(function ($ride) {
                return [
                    'ride_id'             => $ride->id,
                    'customer_name'       => $ride->customer ? $ride->customer->name : 'Khách hàng',
                    'customer_phone'      => $ride->customer ? $ride->customer->phone : null,
                    'pickup_address'      => $ride->pickup_address,
                    'destination_address' => $ride->destination_address,
                    'travel_date'         => $ride->travel_date ? $ride->travel_date->format('Y-m-d') : null,
                    'travel_time'         => $ride->travel_time,
                    'ride_type'           => $ride->ride_type,
                    'ride_type_label'     => $ride->ride_type->getLabel(),
                    'total_price'         => (float) $ride->total_price,
                    'expected_earnings'   => (float) ($ride->total_price * 0.8),
                    'status'              => $ride->status->value,
                    'status_label'        => $ride->status->getLabel(),
                ];
            })->toArray();
        });
    }

    /**
     * @inheritDoc
     */
    public function getAirports(): ServiceReturn
    {
        return $this->execute(function () {
            $airports = $this->airportRepository->getActiveAirports();

            return $airports->map(fn($airport) => [
                'id'   => $airport->id,
                'name' => $airport->name,
                'code' => $airport->code,
                'lat'  => (float) $airport->lat,
                'lng'  => (float) $airport->lng,
            ])->toArray();
        });
    }
    public function listScheduledRidesForAdmin(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters) {
            return $this->rideRepository->listScheduledRidesForAdmin($filters);
        });
    }

    public function listChauffeurRidesForAdmin(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters) {
            return $this->rideRepository->listChauffeurRidesForAdmin($filters);
        });
    }

    public function assignInternalDriver(AssignInternalDriverDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $ride = $this->rideRepository->findById($dto->rideId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
            $this->validate($ride->status === RideStatus::PENDING, 'Chuyến xe đã được phân phối hoặc không hợp lệ.');

            $driver = $this->userRepository->findDriverWithProfileById($dto->driverId);
            $this->validate($driver !== null, 'Không tìm thấy tài xế.', 404);

            // 1. Kiểm tra sự tồn tại của hồ sơ vận hành tài xế
            $this->validate(
                $driver->driverProfile !== null,
                'Tài xế chưa có hồ sơ vận hành hoặc thông tin xe.',
                400
            );

            // 2. Kiểm tra đội xe nhà
            $this->validate(
                $driver->driverProfile->driver_group_type === DriverGroupType::INTERNAL->value,
                'Tài xế này không thuộc đội xe nhà.',
                400
            );

            // 3. Kiểm tra tính đầy đủ của hồ sơ (Tên xe, Biển số xe, Loại xe)
            $this->validate(
                $driver->driverProfile->vehicle_type !== null &&
                !empty($driver->driverProfile->vehicle_number) &&
                !empty($driver->driverProfile->vehicle_name),
                'Hồ sơ tài xế chưa đầy đủ thông tin vận hành (thiếu loại xe, tên xe hoặc biển số xe).',
                400
            );

            // 4. Kiểm tra loại xe có khớp với loại xe yêu cầu của chuyến đi không
            $this->validate(
                $ride->vehicle_type->value === $driver->driverProfile->vehicle_type->value,
                "Loại xe của tài xế ({$driver->driverProfile->vehicle_type->getLabel()}) không khớp với loại xe yêu cầu của chuyến đi ({$ride->vehicle_type->getLabel()}).",
                400
            );

            $success = $this->rideRepository->assignDriver($dto->rideId, $dto->driverId, now());
            $this->validate($success, 'Không thể gán chuyến xe. Vui lòng thử lại.');

            \App\Modules\Ride\Events\RideAssignedByAdmin::dispatch(
                $ride->id,
                $dto->driverId,
                $ride->customer_id
            );

            return [
                'ride_id' => $dto->rideId,
                'driver_id' => $dto->driverId,
                'status' => RideStatus::ACCEPTED->getLabel(),
            ];
        });
    }

    public function pushScheduledRidesToPool(BulkPushToPoolDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $count = $this->rideRepository->pushToPool($dto->rideIds);

            if ($count > 0) {
                \App\Modules\Ride\Events\ScheduledRidesPushedToPool::dispatch($dto->rideIds);
            }

            return [
                'updated_count' => $count,
                'message' => "Đã đẩy $count chuyến xe ra danh sách chung.",
            ];
        });
    }

    /**
     * Kiểm tra xem có nên tự động đẩy chuyến vào pool tài xế ngay khi tạo không.
     *
     * Logic:
     *   - INTERNAL_PRIORITY (Bật chế độ Admin phân phối):
     *       Admin giữ quyền kiểm soát → chuyến xe KHÔNG tự vào pool (is_pushed_to_pool = false)
     *       Admin sẽ tự tay gán tài xế qua màn hình điều phối.
     *
     *   - OPEN_POOL (Tắt chế độ Admin / Tự động):
     *       Hệ thống tự động đẩy chuyến vào pool → tài xế phù hợp thấy và nhận chuyến.
     *
     *   - Không có cấu hình: Mặc định OPEN_POOL (tự động).
     */
    private function shouldPushToPoolImmediately(): bool
    {
        $settings = $this->pricingGlobalSettingRepository->getSettings();

        // Không có cấu hình → mặc định tự động đẩy vào pool
        if (!$settings || $settings->scheduled_dispatch_mode === null) {
            return true;
        }

        // Chỉ tự động push khi mode là OPEN_POOL
        // Khi INTERNAL_PRIORITY (Admin bật chế độ kiểm soát) → không tự push
        return $settings->scheduled_dispatch_mode === ScheduledDispatchMode::OPEN_POOL;
    }

    // =========================================================
    // UC-37: Capture Pickup Proof
    // =========================================================

    /**
     * UC-37: Driver chụp/tải ảnh xác nhận đã lấy hàng thành công.
     *
     * Normal flow: photo + GPS → upload → lưu proof → status = PICKED_UP → phát event.
     * A3/A6 flow:  không có photo → bắt buộc skip_reason + note → xác nhận thủ công → PICKED_UP.
     */
    public function capturePickupProof(CapturePickupProofDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Tìm chuyến xe và xác thực quyền sở hữu của tài xế
            $ride = $this->rideRepository->findByIdAndDriver($dto->rideId, $dto->driverId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            // 2. Chỉ cho phép chụp proof khi tài xế đang ở trạng thái ACCEPTED (đã đến điểm lấy hàng)
            $this->validate(
                $ride->status === RideStatus::ACCEPTED,
                'Chỉ có thể xác nhận lấy hàng khi đang ở trạng thái đã nhận đơn.',
                422
            );

            // 3. Tài xế phải đã báo đến điểm lấy hàng (driver_arrived_at không null)
            $this->validate(
                $ride->driver_arrived_at !== null,
                'Bạn chưa xác nhận đã đến điểm lấy hàng. Vui lòng thực hiện bước đó trước.',
                422
            );

            $capturedAt  = now();
            $photoUrl    = null;

            // 4. Nếu có ảnh → upload lên storage
            if ($dto->photo instanceof UploadedFile) {
                // A2: Kiểm tra kích thước ảnh tối thiểu (ít nhất 100KB để đảm bảo chất lượng)
                $this->validate(
                    $dto->photo->getSize() >= 10240, // >= 10KB (thực tế sẽ là >= 100KB, giảm xuống để demo)
                    'Ảnh quá nhỏ hoặc không rõ ràng. Vui lòng chụp lại để làm bằng chứng.',
                    422
                );

                // Upload ảnh vào private disk: pickup-proofs/{ride_id}/
                $path = FileHelper::uploadToPrivate(
                    $dto->photo,
                    sprintf('pickup-proofs/%s', $dto->rideId)
                );

                $this->validate($path !== false, 'Không thể tải ảnh lên. Vui lòng thử lại.', 500);

                $photoUrl = FileHelper::serveUrl($path);
            } else {
                // A3/A6: Không có ảnh — bắt buộc phải có skip_reason và note
                $this->validate(
                    !empty($dto->skipReason),
                    'Vui lòng chọn lý do không thể chụp ảnh.',
                    422
                );
                $this->validate(
                    !empty($dto->note),
                    'Vui lòng nhập ghi chú khi không thể chụp ảnh.',
                    422
                );
            }

            // 5. Lưu proof vào DB và cập nhật trạng thái → PICKED_UP
            $saved = $this->rideRepository->savePickupProof(
                rideId:      $dto->rideId,
                photoUrl:    $photoUrl,
                capturedAt:  $capturedAt,
                capturedLat: $dto->capturedLat,
                capturedLng: $dto->capturedLng,
                skipReason:  $dto->skipReason,
                note:        $dto->note
            );
            $this->validate($saved, 'Không thể cập nhật trạng thái chuyến xe.', 500);

            // 6. Phát Domain Event để thông báo realtime cho Customer và Merchant
            event(new PickupProofCaptured(
                rideId:      $dto->rideId,
                driverId:    $dto->driverId,
                customerId:  (string) $ride->customer_id,
                photoUrl:    $photoUrl,
                capturedAt:  $capturedAt->toIso8601String(),
                capturedLat: $dto->capturedLat,
                capturedLng: $dto->capturedLng,
                skipReason:  $dto->skipReason,
            ));

            return [
                'ride_id'      => (string) $dto->rideId,
                'status'       => RideStatus::PICKED_UP->value,
                'status_label' => RideStatus::PICKED_UP->getLabel(),
                'photo_url'    => $photoUrl,
                'captured_at'  => $capturedAt->toIso8601String(),
                'is_skipped'   => $photoUrl === null,
                'skip_reason'  => $dto->skipReason,
                'message'      => $photoUrl
                    ? 'Ảnh xác nhận lấy hàng đã được lưu thành công. Bạn có thể bắt đầu giao hàng.'
                    : 'Đã xác nhận thủ công. Bạn có thể bắt đầu giao hàng.',
            ];
        }, useTransaction: true);
    }

    // =========================================================
    // UC-38: Capture Delivery Proof
    // =========================================================

    /**
     * UC-38: Driver chụp/tải ảnh xác nhận đã giao hàng thành công.
     */
    public function captureDeliveryProof(CaptureDeliveryProofDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Xác thực chuyến xe
            $ride = $this->rideRepository->findByIdAndDriver($dto->rideId, $dto->driverId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            // 2. Kiểm tra trạng thái: Phải đang giao hàng (IN_PROGRESS)
            $this->validate(
                $ride->status === RideStatus::IN_PROGRESS,
                'Đơn hàng phải ở trạng thái đang giao mới có thể xác nhận hoàn tất.',
                422
            );

            // 3. A6: Kiểm tra vị trí GPS (Nếu có tọa độ đích và tọa độ chụp)
            // Sai số cho phép: 500m (tùy chỉnh theo yêu cầu thực tế)
            if ($dto->capturedLat && $dto->capturedLng && $ride->destination_lat && $ride->destination_lng) {
                $distance = $this->mapService->calculateDistance(
                    (float) $dto->capturedLat,
                    (float) $dto->capturedLng,
                    (float) $ride->destination_lat,
                    (float) $ride->destination_lng
                );

                $this->validate(
                    $distance <= 500,
                    'Bạn chưa ở đúng vị trí giao hàng (cách điểm đích ' . round($distance) . 'm). Vui lòng di chuyển đến đúng vị trí.',
                    422
                );
            }

            $capturedAt = now();
            $photoUrl   = null;

            // 4. Xử lý ảnh / A3
            if ($dto->photo instanceof UploadedFile) {
                $path = FileHelper::uploadToPrivate(
                    $dto->photo,
                    sprintf('delivery-proofs/%s', $dto->rideId)
                );
                $this->validate($path !== false, 'Không thể tải ảnh lên.', 500);
                $photoUrl = FileHelper::serveUrl($path);
            } else {
                $this->validate(!empty($dto->skipReason), 'Vui lòng chọn lý do không thể chụp ảnh giao hàng.', 422);
                $this->validate(!empty($dto->note), 'Vui lòng nhập ghi chú khi bỏ qua chụp ảnh.', 422);
            }

            // 5. Lưu proof
            $this->rideRepository->saveDeliveryProof(
                rideId:      $dto->rideId,
                photoUrl:    $photoUrl,
                capturedAt:  $capturedAt,
                capturedLat: $dto->capturedLat,
                capturedLng: $dto->capturedLng,
                skipReason:  $dto->skipReason,
                note:        $dto->note
            );

            // 6. Phát sự kiện
            event(new DeliveryProofCaptured(
                rideId:      $dto->rideId,
                driverId:    $dto->driverId,
                customerId:  (string) $ride->customer_id,
                photoUrl:    $photoUrl,
                capturedAt:  $capturedAt->toIso8601String(),
                capturedLat: $dto->capturedLat,
                capturedLng: $dto->capturedLng,
                skipReason:  $dto->skipReason,
            ));

            return [
                'ride_id'      => (string) $dto->rideId,
                'status'       => $ride->status->value,
                'status_label' => $ride->status->getLabel(),
                'photo_url'    => $photoUrl,
                'captured_at'  => $capturedAt->toIso8601String(),
                'message'      => 'Đã lưu bằng chứng giao hàng. Vui lòng bấm hoàn tất chuyến xe để kết thúc.',
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function getDriverRides(GetDriverRidesFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $statuses = null;
            if ($dto->status) {
                $statuses = match ($dto->status) {
                    'processing' => [
                        RideStatus::ACCEPTED->value,
                        RideStatus::PICKED_UP->value,
                        RideStatus::IN_PROGRESS->value,
                        RideStatus::CANCELLATION_REQUESTED->value,
                    ],
                    'completed' => [RideStatus::COMPLETED->value],
                    'cancelled' => [RideStatus::CANCELLED->value],
                    default => $this->throw('Trạng thái lọc không hợp lệ.', 400),
                };
            }

            $paginator = $this->rideRepository->getDriverRides(
                driverId: $dto->driverId,
                statuses: $statuses,
                perPage: $dto->perPage
            );

            // Transform collection to standard response structure
            $paginator->getCollection()->transform(function ($ride) {
                return [
                    'ride_id'             => $ride->id,
                    'customer_name'       => $ride->customer ? $ride->customer->name : 'Khách hàng',
                    'customer_phone'      => $ride->customer ? $ride->customer->phone : null,
                    'pickup_address'      => $ride->pickup_address,
                    'destination_address' => $ride->destination_address,
                    'travel_date'         => $ride->travel_date ? $ride->travel_date->format('Y-m-d') : null,
                    'travel_time'         => $ride->travel_time,
                    'ride_type'           => $ride->ride_type->value,
                    'ride_type_label'     => $ride->ride_type->getLabel(),
                    'total_price'         => (float) $ride->total_price,
                    'expected_earnings'   => (float) ($ride->total_price * 0.8),
                    'status'              => $ride->status->value,
                    'status_label'        => $ride->status->getLabel(),
                    'created_at'          => $ride->created_at ? $ride->created_at->toIso8601String() : null,
                ];
            });

            return $this->success($paginator, 'Lấy danh sách chuyến xe thành công.');
        });
    }
}
