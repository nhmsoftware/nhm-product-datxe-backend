<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Modules\Ride\Model\Ride;

interface RideRepositoryInterface
{
    /**
     * Tìm một ride draft thuộc về khách hàng cụ thể.
     * Dùng để xác thực quyền sở hữu trước khi thực hiện UC-09, UC-10, UC-11.
     */
    public function findByIdAndCustomer(int $rideId, int $customerId): ?Ride;

    /**
     * Áp dụng voucher vào chuyến đi — lưu mã, discount và giá cuối (UC-11 Normal Flow).
     */
    public function applyVoucher(int $rideId, string $voucherCode, float $discountAmount, float $finalPrice): bool;

    /**
     * Xóa voucher khỏi chuyến đi, khôi phục giá gốc (UC-11 A4).
     */
    public function clearVoucher(int $rideId, float $originalPrice): bool;

    /**
     * Xác nhận đặt xe, chuyển trạng thái sang PENDING và chốt giá (UC-12).
     */
    public function confirmBooking(int $rideId, float $finalPrice): bool;
}
