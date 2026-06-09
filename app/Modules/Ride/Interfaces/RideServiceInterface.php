<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\RespondRideCancellationDTO;
use App\Modules\Ride\DTO\AdminCancelRideBookingDTO;
use App\Modules\Ride\DTO\AdminCreateRideBookingDTO;
use App\Modules\Ride\DTO\AdminUpdateRideBookingDTO;
use App\Modules\Ride\DTO\ApplyVoucherDTO;
use App\Modules\Ride\DTO\ConfirmBookingDTO;
use App\Modules\Ride\DTO\CreateDraftRideDTO;
use App\Modules\Ride\DTO\CancelRideDTO;
use App\Modules\Ride\DTO\EstimateRideOptionsDTO;
use App\Modules\Ride\DTO\RequestRideCancellationDTO;

interface RideServiceInterface
{

    /**
     * UC-09: Lấy danh sách loại xe kèm giá ước tính trước khi confirm.
     */
    public function estimateRideOptions(EstimateRideOptionsDTO $dto): ServiceReturn;

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
    public function requestCancellation(\App\Modules\Ride\DTO\RequestRideCancellationDTO $dto): ServiceReturn;

    /**
     * UC-28: Tài xế phản hồi yêu cầu hủy chuyến.
     */
    public function respondToCancellation(RespondRideCancellationDTO $dto): ServiceReturn;

    /**
     * UC-13: Xem thông tin theo dõi chuyến xe (Customer).
     */
    public function showTracking(\App\Modules\Ride\DTO\ShowRideTrackingDTO $dto): ServiceReturn;



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

    /**
     * Đặt xe đi tỉnh (UC-26).
     */
    public function createIntercity(\App\Modules\Ride\DTO\CreateIntercityRideDTO $dto): ServiceReturn;

    /**
     * Đặt xe sân bay (UC-27).
     */
    public function createAirport(\App\Modules\Ride\DTO\CreateAirportRideDTO $dto): ServiceReturn;

    /**
     * Xem chi tiết chuyến xe (UC-29).
     */
    public function getRideDetail(string $rideId, string $customerId): ServiceReturn;

    /**
     * Tài xế xem danh sách chuyến xe đặt trước (UC-47).
     */
    public function getAvailableScheduledRides(\App\Modules\Ride\DTO\FilterScheduledRideDTO $dto): ServiceReturn;

    /**
     * Tài xế xem chi tiết chuyến xe đặt trước (UC-48).
     */
    public function getScheduledRideDetail(string $rideId, string $driverId): ServiceReturn;

    /**
     * Tài xế nhận chuyến xe đặt trước (UC-49).
     */
    public function acceptScheduledRide(string $rideId, string $driverId): ServiceReturn;

    /**
     * Tài xế hủy chuyến xe đặt trước (UC-50).
     */
    public function driverCancelScheduledRide(\App\Modules\Ride\DTO\DriverCancelRideDTO $dto): ServiceReturn;

    /**
     * Tài xế quản lý danh sách chuyến xe đã nhận (UC-51).
     */
    public function getDriverManagedRides(string $driverId): ServiceReturn;

    /**
     * Lấy danh sách sân bay hỗ trợ (UC-27).
     */
    public function getAirports(\App\Modules\Ride\DTO\GetAirportsDTO $dto): ServiceReturn;

    /**
     * Danh sách chuyến đặt trước cho Admin.
     */
    public function listScheduledRidesForAdmin(array $filters): ServiceReturn;

    /**
     * Chi tiết chuyến đặt xe cho Admin.
     */
    public function getAdminRideDetail(string $rideId): ServiceReturn;

    /**
     * Tạo booking chuyến xe thủ công từ Admin Portal.
     */
    public function createAdminRideBooking(AdminCreateRideBookingDTO $dto): ServiceReturn;

    /**
     * Cập nhật booking chuyến xe từ Admin Portal.
     */
    public function updateAdminRideBooking(AdminUpdateRideBookingDTO $dto): ServiceReturn;

    /**
     * Hủy / xóa mềm booking chuyến xe từ Admin Portal.
     */
    public function cancelAdminRideBooking(AdminCancelRideBookingDTO $dto): ServiceReturn;

    /**
     * Danh sách chuyến Lái hộ cho Admin.
     */
    public function listChauffeurRidesForAdmin(array $filters): ServiceReturn;

    /**
     * Danh sách đơn dịch vụ (Giao hàng, Đồ ăn) cho Admin.
     * Chỉ lấy ride_type = DELIVERY(4) và FOOD_DELIVERY(6).
     */
    public function listServiceOrdersForAdmin(array $filters): ServiceReturn;

    /**
     * Admin gán chuyến xe cho tài xế đội xe nhà.
     */
    public function assignInternalDriver(\App\Modules\Ride\DTO\AssignInternalDriverDTO $dto): ServiceReturn;

    /**
     * Admin đẩy chuyến xe ra pool cho tài xế ngoài.
     */
    public function pushScheduledRidesToPool(\App\Modules\Ride\DTO\BulkPushToPoolDTO $dto): ServiceReturn;

    /**
     * UC-25: Tạo đơn giao hàng.
     * Bao gồm tạo Ride với type DELIVERY và DeliveryOrder đính kèm.
     */
    public function createDeliveryOrder(\App\Modules\Ride\DTO\CreateDeliveryOrderDTO $dto): ServiceReturn;

    /**
     * UC-37: Driver chụp/tải ảnh xác nhận đã lấy hàng thành công.
     * Hỗ trợ 2 luồng:
     *  - Normal: Có ảnh (photo) + GPS → upload & lưu proof → PICKED_UP.
     *  - A3/A6:  Không ảnh → bắt buộc skip_reason + note → xác nhận thủ công → PICKED_UP.
     *
     * @param \App\Modules\Ride\DTO\CapturePickupProofDTO $dto
     * @return ServiceReturn
     */
    public function capturePickupProof(\App\Modules\Ride\DTO\CapturePickupProofDTO $dto): ServiceReturn;

    /**
     * UC-38: Driver chụp/tải ảnh xác nhận đã giao hàng thành công.
     * Tương tự UC-37 nhưng chuyển trạng thái về COMPLETED và hoàn tất đơn hàng.
     *
     * @param \App\Modules\Ride\DTO\CaptureDeliveryProofDTO $dto
     * @return ServiceReturn
     */
    public function captureDeliveryProof(\App\Modules\Ride\DTO\CaptureDeliveryProofDTO $dto): ServiceReturn;

    /**
     * Tài xế lấy danh sách chuyến xe (lịch sử/đang xử lý) (UC-51.1).
     */
    public function getDriverRides(\App\Modules\Ride\DTO\GetDriverRidesFilterDTO $dto): ServiceReturn;
}
