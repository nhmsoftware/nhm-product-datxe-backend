<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Modules\Ride\Model\Ride;
use Carbon\Carbon;

interface RideRepositoryInterface
{
    /**
     * Tìm một ride draft thuộc về khách hàng cụ thể.
     * Dùng để xác thực quyền sở hữu trước khi thực hiện UC-09, UC-10, UC-11.
     * @param int $rideId ID chuyến chuyến
     * @param int $customerId ID khách hàng
     * @return Ride|null
     */
    public function findByIdAndCustomer(int $rideId, int $customerId): ?Ride;

    /**
     * Áp dụng voucher vào chuyến đi — lưu mã, discount và giá cuối (UC-11 Normal Flow).
     * @param int $rideId ID chuyến chuyến
     * @param string $voucherCode Mã voucher
     * @param float $discountAmount Số tiền giảm giá
     * @param float $finalPrice Giá cuối
     * @return bool
     */
    public function applyVoucher(int $rideId, string $voucherCode, float $discountAmount, float $finalPrice): bool;

    /**
     * Xóa voucher khỏi chuyến đi, khôi phục giá gốc (UC-11 A4).
     * @param int $rideId ID chuyến chuyến
     * @param float $originalPrice Giá gốc
     * @return bool
     */
    public function clearVoucher(int $rideId, float $originalPrice): bool;

    /**
     * Xác nhận đặt xe, chuyển trạng thái sang PENDING và chốt giá (UC-12).
     * @param int $rideId ID chuyến chuyến
     * @param float $finalPrice Giá cuối
     * @return bool
     */
    public function confirmBooking(int $rideId, float $finalPrice): bool;

    /**
     * Hủy chuyến đi, cập nhật lý do và phí hủy nếu có (UC-15).
     * @param int $rideId ID chuyến chuyến
     * @param string|null $reason Lý do hủy
     * @param float $cancellationFee Phi phí hủy
     * @return bool
     */
    public function cancel(int $rideId, ?string $reason, float $cancellationFee): bool;

    /**
     * Tính toán tổng chi tiêu của khách hàng trong một khoảng thời gian (UC-23).
     *
     * @param int $customerId
     * @param Carbon $start
     * @param Carbon $end
     * @return array{total_amount: float, total_count: int}
     */
    public function getSpendingSummary(int $customerId, Carbon $start, Carbon $end): array;

    /**
     * Kiểm tra tài xế có chuyến đi nào đang diễn ra không (UC-31).
     * @param int $driverId ID tài khoản tài xế
     * @return bool
     */
    public function hasActiveRideByDriver(int $driverId): bool;

    /**
     * Tài xế nhận chuyến đi — cập nhật status ACCEPTED và gán driver_id (UC-32).
     * @param int $rideId ID chuyến đi
     * @param int $driverId ID tài khoản tài xế
     * @return bool
     */
    public function acceptByDriver(int $rideId, int $driverId): bool;

    /**
     * Tài xế từ chối nhận đơn (UC-33 Reject).
     * @param int $rideId
     * @param int $driverId
     * @return bool
     */
    public function rejectByDriver(int $rideId, int $driverId): bool;

    /**
     * Tài xế hủy chuyến sau khi đã nhận (UC-33 Cancel).
     * @param int $rideId
     * @param int $reasonId
     * @return bool
     */
    public function cancelByDriver(int $rideId, int $reasonId): bool;
}
