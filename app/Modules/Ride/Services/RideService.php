<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\DTO\PricingResultDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\DTO\RequestRideCancellationDTO;
use App\Modules\Driver\DTO\RespondRideCancellationDTO;
use App\Modules\Ride\DTO\PriceEstimateDTO;
use App\Modules\Ride\DTO\VehicleOptionDTO;
use App\Modules\Ride\Events\RideBooked;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Events\RideCancellationRequested;
use App\Modules\Ride\Events\RideCancellationResponded;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Interfaces\RideServiceInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\User;
use App\Modules\Ride\Interfaces\MapServiceInterface;
use App\Modules\Finance\Interfaces\VoucherRepositoryInterface;
use App\Modules\Finance\Model\Enums\VoucherDiscountType;
use App\Modules\Finance\Model\Enums\VoucherServiceType;

final class RideService extends BaseService implements RideServiceInterface
{
    public function __construct(
        private readonly RideRepositoryInterface     $rideRepository,
        private readonly MapServiceInterface          $mapService,
        private readonly PricingServiceInterface      $pricingService,
        private readonly UserRepositoryInterface      $userRepository,
        private readonly VoucherRepositoryInterface   $voucherRepository
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
                'time_fare'          => $pricingData->timeFare,
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
    public function getVehicleOptions(string $rideId, string $customerId): ServiceReturn
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
    public function getPriceEstimate(string $rideId, string $customerId): ServiceReturn
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
                baseFare:        $pricingData->baseFare, // Giá vé cơ bản
                distanceFare:    $pricingData->distanceFare, // Giá vé theo khoảng cách
                timeFare:        $pricingData->timeFare, // Giá vé theo thời gian
                surgeMultiplier: $pricingData->surgeMultiplier, //
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

            // Tính giá cước hiện tại
            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();

            // Validate voucher với giá cước hiện tại và lấy giá trị discount
            $discountAmount = $this->resolveVoucherDiscount($dto->voucherCode, $pricingData->finalFare);
            $this->validate($discountAmount !== null, 'Mã giảm giá không hợp lệ hoặc không thể áp dụng.');

            $finalFare = max(0, $pricingData->finalFare - $discountAmount);

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
    public function removeVoucher(string $rideId, string $customerId): ServiceReturn
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

            // 1. A1 - Tính toán lại giá cước để kiểm tra có thay đổi không
            $pricingResult = $this->calculatePriceFor(
                distanceMeters: $ride->distance,
                durationSeconds: $ride->duration,
                vehicleType: $ride->vehicle_type
            );
            $this->validate(!$pricingResult->isError(), $pricingResult->getMessage());

            /** @var PricingResultDTO $pricingData */
            $pricingData = $pricingResult->getData();
            $discountAmount = 0.0;

            // 2. A2 - Kiểm tra Voucher nếu có
            if (!empty($ride->voucher_code)) {
                $discountAmount = $this->resolveVoucherDiscount($ride->voucher_code, $pricingData->finalFare);

                // Nếu voucher không hợp lệ (hết hạn/ko đủ min_fare)
                if ($discountAmount === null) {
                    // Hủy voucher và yêu cầu xác nhận lại
                    $this->rideRepository->clearVoucher($dto->rideId, $pricingData->finalFare);
                    $this->throw('Voucher không còn khả dụng. Giá cước đã thay đổi, vui lòng xác nhận lại.', 409);
                }
            }

            $finalFare = max(0, $pricingData->finalFare - $discountAmount);

            // 3. A1 - Kiểm tra xem giá có bị thay đổi so với $dto->expectedPrice không (cho phép sai số 1đ)
            if (abs($finalFare - $dto->expectedPrice) > 1.0) {
                // Update giá mới nhất vào draft
                // We use update here temporarily for fixing the price, normally we do save/update.
                // Using clearVoucher/applyVoucher correctly updates total_price, but let's just update directly or through applyVoucher.
                if (!empty($ride->voucher_code)) {
                    $this->rideRepository->applyVoucher($dto->rideId, $ride->voucher_code, $discountAmount, $finalFare);
                } else {
                    $this->rideRepository->clearVoucher($dto->rideId, $finalFare);
                }

                $this->throw('Giá cước đã thay đổi do tình hình giao thông, vui lòng xác nhận lại giá mới.', 409);
            }

            // 4. Nếu mọi thứ đúng, xác nhận chuyến đi (PENDING)
            $this->rideRepository->confirmBooking($dto->rideId, $finalFare);

            // 5. Kích hoạt Domain Event để hệ thống tìm tài xế (A4, A6 sẽ được handle async/background)
            event(new RideBooked($dto->rideId, $dto->customerId));

            // Return thông tin (sẽ pass qua getPriceEstimate hoặc mảng tùy ý, trả về mảng info cơ bản là okay)
            $ride->refresh();
            return $this->success($ride->toArray(), 'Đặt xe thành công. Đang tìm tài xế.');
        }, useTransaction: true);
    }

    /**
     * UC-15: Hủy chuyến xe
     */
    public function cancelRide(CancelRideDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            // A2: Chuyến đã bắt đầu (không cho phép hủy)
            $this->validate(
                $ride->status !== RideStatus::IN_PROGRESS,
                'Không thể hủy chuyến khi đã bắt đầu di chuyển.'
            );

            // Kiểm tra xem đã kết thúc hay đã hủy chưa
            $this->validate(
                !$ride->status->isTerminal(),
                'Chuyến xe này đã hoàn thành hoặc đã bị hủy trước đó.'
            );

            $cancellationFee = 0.0;

            // A3: Áp dụng phí hủy chuyến (Nếu đã có driver nhận - ACCEPTED)
            if ($ride->status === RideStatus::ACCEPTED) {
                // TODO: Lấy phí hủy từ hệ thống config hoặc module Pricing
                $cancellationFee = 10000.0; // Phí mặc định 10k
            }

            // Thực hiện hủy trong DB
            $this->rideRepository->cancel($dto->rideId, $dto->reason, $cancellationFee);

            // Raise Domain Event để thông báo cho Driver (Nếu có)
            event(new RideCanceled($dto->rideId, $dto->customerId, $ride->driver_id));

            return [
                'ride_id'          => $dto->rideId,
                'status'           => RideStatus::CANCELLED->getLabel(),
                'cancellation_fee' => $cancellationFee,
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function requestCancellation(RequestRideCancellationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $ride = $this->rideRepository->findByIdAndCustomer($dto->rideId, $dto->customerId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);

            // A2: Chuyến xe đã bắt đầu
            $this->validate(
                $ride->status !== RideStatus::IN_PROGRESS,
                'Không thể hủy chuyến khi chuyến đi đã bắt đầu.',
                400
            );

            // Nếu chưa có tài xế nhận (PENDING) -> Hủy ngay
            if ($ride->status === RideStatus::PENDING || $ride->status === RideStatus::DRAFT) {
                $this->rideRepository->updateStatus($dto->rideId, RideStatus::CANCELLED, $dto->reason);
                event(new RideCanceled($dto->rideId, $dto->customerId, null));

                return [
                    'ride_id' => $dto->rideId,
                    'status'  => RideStatus::CANCELLED->value,
                    'message' => 'Hủy chuyến thành công.',
                ];
            }

            // Nếu đã có tài xế nhận -> Yêu cầu xác nhận
            $this->validate(
                $ride->driver_id !== null,
                'Trạng thái chuyến xe không hợp lệ để yêu cầu hủy.',
                400
            );

            $this->rideRepository->updateStatus($dto->rideId, RideStatus::CANCELLATION_REQUESTED, $dto->reason);
            event(new RideCancellationRequested($dto->rideId, (string)$ride->driver_id, $dto->customerId, $dto->reason));

            return [
                'ride_id' => $dto->rideId,
                'status'  => RideStatus::CANCELLATION_REQUESTED->value,
                'message' => 'Đang chờ tài xế xác nhận hủy chuyến.',
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function respondToCancellation(RespondRideCancellationDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $ride = $this->rideRepository->find($dto->rideId);
            $this->validate($ride !== null, 'Không tìm thấy chuyến xe.', 404);
            $this->validate((string)$ride->driver_id === $dto->driverId, 'Bạn không có quyền phản hồi yêu cầu này.', 403);
            $this->validate($ride->status === RideStatus::CANCELLATION_REQUESTED, 'Yêu cầu hủy không tồn tại hoặc đã được xử lý.', 400);

            if ($dto->isApproved) {
                // Driver đồng ý hủy
                $this->rideRepository->updateStatus($dto->rideId, RideStatus::CANCELLED);
                $message = 'Chuyến xe đã được hủy.';
            } else {
                // Driver từ chối hủy -> Quay lại trạng thái ACCEPTED (hoặc trạng thái logic trước đó)
                // Theo spec A1: System giữ nguyên trạng thái chuyến xe (trước khi request)
                // Ở đây ta mặc định quay lại ACCEPTED
                $this->rideRepository->updateStatus($dto->rideId, RideStatus::ACCEPTED);
                $message = 'Tài xế không đồng ý hủy chuyến.';
            }

            event(new RideCancellationResponded($dto->rideId, (string)$ride->customer_id, $dto->driverId, $dto->isApproved));

            return [
                'ride_id' => $dto->rideId,
                'status'  => $ride->refresh()->status->value,
                'message' => $message,
            ];
        }, useTransaction: true);
    }

    /**
     * Tính giá cước qua PricingService.
     * Private helper để tránh lặp code — chuyển đổi đơn vị và gọi service.
     * @param int $distanceMeters Khoảng cách (mét)
     * @param int $durationSeconds Thời gian (giây)
     * @param VehicleType $vehicleType Loại xe
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
     * Trả về null nếu voucher không hợp lệ.
     *
     * @param string $code Mã voucher
     * @param float $currentFare Giá cước hiện tại để kiểm tra điều kiện min_fare
     * @return float|null Số tiền giảm giá hoặc null nếu không hợp lệ
     */
    private function resolveVoucherDiscount(string $code, float $currentFare): ?float
    {
        $code = strtoupper(trim($code));
        if (strlen($code) < 3) {
            return null;
        }

        $voucher = $this->voucherRepository->findByCode($code);

        if (!$voucher) {
            $this->throw('Mã giảm giá không tồn tại.', 409);
        }

        // 1. Kiểm tra tính hợp lệ cơ bản (Active, Thời hạn, Số lượng)
        if (!$voucher->isValid()) {
            if ($voucher->isExpired()) {
                $this->throw('Mã giảm giá đã hết hạn hoặc hết lượt sử dụng.', 409);
            }
            $this->throw('Mã giảm giá không khả dụng hiện tại.', 409);
        }

        // 2. Kiểm tra loại dịch vụ (Phải áp dụng cho Ride)
        $allowedServices = [VoucherServiceType::RIDE, VoucherServiceType::BOTH, VoucherServiceType::ALL];
        if (!in_array($voucher->service_type, $allowedServices)) {
            $this->throw('Mã giảm giá không áp dụng cho dịch vụ đặt xe.', 409);
        }

        // 3. Kiểm tra Min Order Amount
        if ($currentFare < $voucher->min_order_amount) {
            $this->throw(
                sprintf('Giá trị đơn hàng chưa đủ để áp dụng mã này (Tối thiểu %s VNĐ).', number_format($voucher->min_order_amount)),
                409
            );
        }

        // 4. Tính toán số tiền giảm
        $discount = 0.0;
        if ($voucher->discount_type === VoucherDiscountType::FIXED) {
            $discount = $voucher->discount_value;
        } elseif ($voucher->discount_type === VoucherDiscountType::PERCENT) {
            $discount = ($currentFare * $voucher->discount_value) / 100;

            // Áp dụng trần giảm giá nếu có
            if ($voucher->max_discount_amount > 0) {
                $discount = min($discount, $voucher->max_discount_amount);
            }
        }

        return (float) $discount;
    }
}
