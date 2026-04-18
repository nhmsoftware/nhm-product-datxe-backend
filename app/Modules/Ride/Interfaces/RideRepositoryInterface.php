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
     * @param string $rideId ID chuyến chuyến
     * @param string $customerId ID khách hàng
     * @return Ride|null
     */
    public function findByIdAndCustomer(string $rideId, string $customerId): ?Ride;

    /**
     * Áp dụng voucher vào chuyến đi — lưu mã, discount và giá cuối (UC-11 Normal Flow).
     * @param string $rideId ID chuyến chuyến
     * @param string $voucherCode Mã voucher
     * @param float $discountAmount Số tiền giảm giá
     * @param float $finalPrice Giá cuối
     * @return bool
     */
    public function applyVoucher(string $rideId, string $voucherCode, float $discountAmount, float $finalPrice): bool;

    /**
     * Xóa voucher khỏi chuyến đi, khôi phục giá gốc (UC-11 A4).
     * @param string $rideId ID chuyến chuyến
     * @param float $originalPrice Giá gốc
     * @return bool
     */
    public function clearVoucher(string $rideId, float $originalPrice): bool;

    /**
     * Xác nhận đặt xe, chuyển trạng thái sang PENDING và chốt giá (UC-12).
     * @param string $rideId ID chuyến chuyến
     * @param float $finalPrice Giá cuối
     * @return bool
     */
    public function confirmBooking(string $rideId, float $finalPrice): bool;

    /**
     * Hủy chuyến đi, cập nhật lý do và phí hủy nếu có (UC-15).
     * @param string $rideId ID chuyến chuyến
     * @param string|null $reason Lý do hủy
     * @param float $cancellationFee Phi phí hủy
     * @return bool
     */
    public function cancel(string $rideId, ?string $reason, float $cancellationFee): bool;

    /**
     * Tính toán tổng chi tiêu của khách hàng trong một khoảng thời gian (UC-23).
     *
     * @param string $customerId ID khách hàng
     * @param Carbon $start
     * @param Carbon $end
     * @return array{total_amount: float, total_count: int}
     */
    public function getSpendingSummary(string $customerId, Carbon $start, Carbon $end): array;

    /**
     * Kiểm tra tài xế có chuyến đi nào đang diễn ra không (UC-31).
     * @param string $driverId ID tài khoản tài xế
     * @return bool
     */
    public function hasActiveRideByDriver(string $driverId): bool;

    /**
     * Tài xế nhận chuyến đi — cập nhật status ACCEPTED và gán driver_id (UC-32).
     * @param string $rideId ID chuyến đi
     * @param string $driverId ID tài khoản tài xế
     * @return bool
     */
    public function acceptByDriver(string $rideId, string $driverId): bool;

    /**
     * Tài xế từ chối nhận đơn (UC-33 Reject).
     * @param string $rideId ID chuyến đi
     * @param string $driverId ID tài khoản tài xế
     * @return bool
     */
    public function rejectByDriver(string $rideId, string $driverId): bool;

    /**
     * Tài xế hủy chuyến sau khi đã nhận (UC-33 Cancel).
     * @param string $rideId ID chuyến đi
     * @param string $reasonId ID lý do hủy
     * @return bool
     */
    public function cancelByDriver(string $rideId, string $reasonId): bool;

    /**
     * Tài xế xác nhận đã đón khách thành công (UC-36).
     * @param string $rideId ID chuyến đi
     * @return bool
     */
    public function pickup(string $rideId): bool;

    /**
     * Tài xế bắt đầu thực hiện chuyến đi (UC-35 Start Trip).
     * @param string $rideId ID chuyến đi
     * @return bool
     */
    public function startTrip(string $rideId): bool;

    /**
     * Tài xế hoàn thành chuyến đi (UC-40 Complete Trip).
     * @param string $rideId ID chuyến đi
     * @param float $finalFare Giá cuối cùng
     * @return bool
     */
    public function completeTrip(string $rideId, float $finalFare): bool;
}
