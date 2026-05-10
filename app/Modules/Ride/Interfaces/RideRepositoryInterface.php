<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Modules\Ride\Model\Ride;
use Carbon\CarbonInterface;

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
     * Tìm chi tiết chuyến xe kèm thông tin tài xế (UC-29).
     */
    public function findWithDriverDetail(string $rideId, string $customerId): ?Ride;

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
    public function getSpendingSummary(string $customerId, CarbonInterface $start, CarbonInterface $end): array;

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
     * @param float $serviceFee Phí dịch vụ
     * @param float $driverEarnings Thu nhập thực nhận
     * @return bool
     */
    public function completeTrip(string $rideId, float $finalFare, float $serviceFee, float $driverEarnings): bool;

    /**
     * @param string $driverId ID tài khoản tài xế
     * @return bool TRUE nếu đã từ chối/hủy, FALSE nếu chưa
     */
    public function isRejectedByDriver(string $rideId, string $driverId): bool;

    /**
     * Cập nhật trạng thái chuyến xe linh hoạt.
     */
    public function updateStatus(string $rideId, \App\Modules\Ride\Model\Enums\RideStatus $status, ?string $reason = null): bool;

    /**
     * Tìm chuyến xe đang diễn ra của tài xế.
     * @param string $driverId
     * @return Ride|null
     */
    public function findActiveByDriver(string $driverId): ?Ride;

    /**
     * Tìm chuyến xe đang diễn ra của khách hàng.
     * @param string $customerId
     * @return Ride|null
     */
    public function findActiveByCustomer(string $customerId): ?Ride;

    /**
     * Tìm một ride cho việc tracking snapshot (UC-13).
     */
    public function findTrackingRideByIdAndCustomer(string $rideId, string $customerId): ?Ride;

    /**
     * Tài xế chấp nhận chuyến — gán driver_id và chuyển sang ACCEPTED.
     */
    public function assignDriver(string $rideId, string $driverId, CarbonInterface $acceptedAt): bool;

    /**
     * Tìm ride cho driver tracking snapshot (UC-13).
     */
    public function findTrackingRideByIdAndDriver(string $rideId, string $driverId): ?Ride;

    /**
     * Cập nhật timestamp lần cuối nhận tín hiệu từ tài xế (Heartbeat).
     */
    public function refreshTrackingHeartbeat(string $rideId, CarbonInterface $trackedAt): bool;

    /**
     * Tài xế đã đến điểm đón (UC-13).
     */
    public function markDriverArrived(string $rideId, CarbonInterface $arrivedAt): bool;

    /**
     * Tài xế hủy chuyến sau khi nhận — đưa ride về PENDING và xóa driver_id.
     */
    public function releaseDriverFromRide(string $rideId, ?string $reason): bool;

    /**
     * Đếm số lượng cuốc xe tài xế đã hủy trong ngày hôm nay.
     */
    public function countCancellationsToday(string $driverId): int;

    /**
     * Tạo chuyến xe đi tỉnh (UC-26).
     */
    public function createIntercityRide(array $data): \App\Modules\Ride\Model\Ride;

    /**
     * Tạo chuyến xe sân bay (UC-27).
     */
    public function createAirportRide(array $data): \App\Modules\Ride\Model\Ride;

    /**
     * Tìm danh sách chuyến xe đặt trước đang chờ tài xế (UC-47).
     *
     * @param int $vehicleType Loại xe của tài xế
     * @param array $filters Bộ lọc tìm kiếm
     * @return \Illuminate\Support\Collection
     */
    public function findAvailableScheduledRides(int $vehicleType, array $filters): \Illuminate\Support\Collection;

    /**
     * Tìm chuyến xe đang chờ tài xế theo ID (UC-48).
     */
    public function findAvailableById(string $rideId): ?\App\Modules\Ride\Model\Ride;

    /**
     * Tìm danh sách các chuyến xe mà tài xế đã nhận (UC-51).
     */
    public function findDriverAcceptedRides(string $driverId): \Illuminate\Support\Collection;

    /**
     * Đếm tổng số chuyến xe trong hệ thống.
     */
    public function countTotalOrders(): int;

    /**
     * Tính tổng doanh thu hệ thống (các chuyến xe đã hoàn thành).
     */
    public function sumTotalRevenue(): float;

    /**
     * Danh sách chuyến đặt trước cho Admin quản lý.
     */
    public function listScheduledRidesForAdmin(array $filters);

    /**
     * Đẩy các chuyến xe ra pool cho tất cả tài xế ngoài.
     */
    public function pushToPool(array $rideIds): int;

    /**
     * Đẩy toàn bộ chuyến đặt trước đang chờ ra pool.
     * Được gọi khi Admin chuyển sang chế độ Open Pool (Tự động).
     */
    public function pushAllPendingScheduledToPool(): int;

    /**
     * Ẩn toàn bộ chuyến đặt trước đang chờ khỏi pool tài xế.
     * Được gọi khi Admin bật chế độ Admin Priority (Thủ công).
     */
    public function hideAllPendingScheduledFromPool(): int;

    // =========================================================
    // UC-25: Giao hàng (Delivery)
    // =========================================================

    /**
     * Tạo chuyến xe với RideType::DELIVERY.
     */
    public function createDeliveryRide(array $data): \App\Modules\Ride\Model\Ride;

    /**
     * Tạo bản ghi DeliveryOrder đính kèm thông tin giao hàng.
     */
    public function createDeliveryOrderDetail(array $data): \App\Modules\Ride\Model\DeliveryOrder;

    // =========================================================
    // UC-37: Capture Pickup Proof
    // =========================================================

    /**
     * Lưu bằng chứng lấy hàng (ảnh hoặc xác nhận thủ công A3/A6).
     *
     * @param string      $rideId        ID chuyến xe
     * @param string|null $photoUrl      URL ảnh đã upload lên storage (null nếu A3/A6)
     * @param \Carbon\CarbonInterface $capturedAt Thời điểm chụp
     * @param float|null  $capturedLat   Vĩ độ GPS khi chụp
     * @param float|null  $capturedLng   Kinh độ GPS khi chụp
     * @param string|null $skipReason    Lý do bỏ qua (A3/A6)
     * @param string|null $note          Ghi chú thêm (A3/A6)
     * @return bool
     */
    public function savePickupProof(
        string $rideId,
        ?string $photoUrl,
        \Carbon\CarbonInterface $capturedAt,
        ?float $capturedLat,
        ?float $capturedLng,
        ?string $skipReason,
        ?string $note
    ): bool;

    /**
     * Lưu bằng chứng giao hàng (UC-38).
     *
     * @param string      $rideId
     * @param string|null $photoUrl
     * @param \Carbon\CarbonInterface $capturedAt
     * @param float|null  $capturedLat
     * @param float|null  $capturedLng
     * @param string|null $skipReason
     * @param string|null $note
     * @return bool
     */
    public function saveDeliveryProof(
        string $rideId,
        ?string $photoUrl,
        \Carbon\CarbonInterface $capturedAt,
        ?float $capturedLat,
        ?float $capturedLng,
        ?string $skipReason,
        ?string $note
    ): bool;

    /**
     * Tìm chuyến xe theo ID và Driver ID (dùng để xác thực quyền sở hữu trong UC-37).
     */
    public function findByIdAndDriver(string $rideId, string $driverId): ?\App\Modules\Ride\Model\Ride;

    /**
     * Lấy dữ liệu phân tích doanh thu theo thời gian (UC-Dashboard).
     *
     * @param \Carbon\CarbonInterface $start
     * @param \Carbon\CarbonInterface $end
     * @param string $interval (day, month, year)
     * @return array
     */
    public function getRevenueAnalytics(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end, string $interval): array;

    /**
     * Lấy dữ liệu phân tích doanh thu theo khu vực (UC-Dashboard).
     *
     * @param \Carbon\CarbonInterface $start
     * @param \Carbon\CarbonInterface $end
     * @return array
     */
    public function getAreaAnalytics(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): array;

    /**
     * Lấy thống kê vận hành đơn hàng (UC-Dashboard).
     *
     * @param \Carbon\CarbonInterface $start
     * @param \Carbon\CarbonInterface $end
     * @return array
     */
    public function getOrderOperationalStats(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): array;

    /**
     * Lấy dữ liệu phân tích hoa hồng (UC-Dashboard).
     *
     * @param \Carbon\CarbonInterface $start
     * @param \Carbon\CarbonInterface $end
     * @return array
     */
    public function getCommissionAnalytics(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): array;

    /**
     * Lấy danh sách chi tiết hoa hồng (UC-Dashboard).
     *
     * @param \Carbon\CarbonInterface $start
     * @param \Carbon\CarbonInterface $end
     * @param int $limit
     * @return array
     */
    public function getCommissionDetails(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end, int $limit = 50): array;

    /**
     * Lấy phân tích theo loại xe (UC-Dashboard).
     */
    public function getVehicleTypeAnalytics(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): array;

    /**
     * Lấy phân tích theo loại dịch vụ (Standard, Intercity, Airport, Delivery) (UC-Dashboard).
     */
    public function getRideTypeAnalytics(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): array;

    /**
     * Lấy danh sách Top tài xế theo doanh thu (UC-Dashboard).
     */
    public function getTopDriversAnalytics(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end, int $limit = 10, ?int $driverGroupType = null): array;
}
