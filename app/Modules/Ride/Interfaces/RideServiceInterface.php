<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\RespondRideCancellationDTO;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\DTO\RequestRideCancellationDTO;

interface RideServiceInterface
{
    /**
     * Tạo bản nháp chuyến xe (UC-08).
     *
     * @param CreateDraftRideDTO $dto Thông tin chuyến xe nháp
     * @return ServiceReturn
     */
    public function createDraft(CreateDraftRideDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách loại xe khả dụng kèm giá ước tính (UC-09).
     * Dựa vào khoảng cách & thời gian từ draft ride đã tạo.
     *
     * @param string $rideId ID của ride draft
     * @param string $customerId ID của khách hàng
     * @return ServiceReturn Danh sách VehicleOptionDTO[]
     */
    public function getVehicleOptions(string $rideId, string $customerId): ServiceReturn;

    /**
     * Xem chi tiết giá ước tính cho chuyến đi (UC-10).
     * Tính lại giá dựa trên loại xe đã chọn.
     *
     * @param string $rideId ID của ride draft
     * @param string $customerId ID của khách hàng
     * @return ServiceReturn PriceEstimateDTO
     */
    public function getPriceEstimate(string $rideId, string $customerId): ServiceReturn;

    /**
     * Áp dụng mã giảm giá vào chuyến đi (UC-11).
     * Kiểm tra tính hợp lệ của voucher và cập nhật giá.
     *
     * @param ApplyVoucherDTO $dto Mã voucher và ride ID
     * @return ServiceReturn PriceEstimateDTO sau khi đã áp dụng giảm giá
     */
    public function applyVoucher(ApplyVoucherDTO $dto): ServiceReturn;

    /**
     * Xóa voucher đã áp dụng khỏi chuyến đi (UC-11 - A4).
     *
     * @param string $rideId ID của ride draft
     * @param string $customerId ID của khách hàng
     * @return ServiceReturn PriceEstimateDTO sau khi đã xóa giảm giá
     */
    public function removeVoucher(string $rideId, string $customerId): ServiceReturn;

    /**
     * UC-28: Khách hàng yêu cầu hủy chuyến.
     */
    public function requestCancellation(RequestRideCancellationDTO $dto): \App\Core\Services\ServiceReturn;

    /**
     * UC-28: Tài xế phản hồi yêu cầu hủy chuyến.
     */
    public function respondToCancellation(RespondRideCancellationDTO $dto): \App\Core\Services\ServiceReturn;

    /**
     * Xác nhận đặt xe (UC-12).
     *
     * @return ServiceReturn
     */
    public function confirmBooking(ConfirmBookingDTO $dto): ServiceReturn;

    /**
     * Hủy chuyến xe (UC-15).
     *
     * @param CancelRideDTO $dto Thông tin yêu cầu hủy
     * @return ServiceReturn
     */
    public function cancelRide(CancelRideDTO $dto): ServiceReturn;
}
