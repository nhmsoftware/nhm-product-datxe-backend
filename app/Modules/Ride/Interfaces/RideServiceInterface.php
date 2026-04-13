<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;

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
     * @param int $rideId ID của ride draft
     * @param int $customerId ID của khách hàng
     * @return ServiceReturn Danh sách VehicleOptionDTO[]
     */
    public function getVehicleOptions(int $rideId, int $customerId): ServiceReturn;

    /**
     * Xem chi tiết giá ước tính cho chuyến đi (UC-10).
     * Tính lại giá dựa trên loại xe đã chọn.
     *
     * @param int $rideId ID của ride draft
     * @param int $customerId ID của khách hàng
     * @return ServiceReturn PriceEstimateDTO
     */
    public function getPriceEstimate(int $rideId, int $customerId): ServiceReturn;

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
     * @param int $rideId ID của ride draft
     * @param int $customerId ID của khách hàng
     * @return ServiceReturn PriceEstimateDTO sau khi đã xóa giảm giá
     */
    public function removeVoucher(int $rideId, int $customerId): ServiceReturn;
}
