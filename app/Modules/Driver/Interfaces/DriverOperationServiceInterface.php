<?php

declare(strict_types=1);

namespace App\Modules\Driver\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\AcceptOrderDTO;
use App\Modules\Driver\DTO\CancelOrderDTO;
use App\Modules\Driver\DTO\RejectOrderDTO;
use App\Modules\Driver\DTO\PickupRideDTO;
use App\Modules\Driver\DTO\StartRideDTO;
use App\Modules\Driver\DTO\CompleteRideDTO;
use App\Modules\Driver\DTO\RespondRideCancellationDTO;

interface DriverOperationServiceInterface
{
    /**
     * Thông báo tài xế đã đến điểm đón (A1 UC-36).
     * @param PickupRideDTO $dto
     * @return ServiceReturn
     */
    public function notifyArrived(PickupRideDTO $dto): ServiceReturn;

    /**
     * Xác nhận đã đón khách (UC-36).
     * @param PickupRideDTO $dto
     * @return ServiceReturn
     */
    public function pickupRide(PickupRideDTO $dto): ServiceReturn;

    /**
     * Cập nhật trạng thái trực tuyến của Driver (UC-31).
     */
    public function toggleOnlineStatus(ToggleOnlineStatusDTO $dto): ServiceReturn;

    /**
     * Tài xế nhận chuyến đi (UC-32).
     */
    public function acceptOrder(AcceptOrderDTO $dto): ServiceReturn;

    /**
     * Tài xế từ chối chuyến đi (UC-33 Reject).
     */
    public function rejectOrder(RejectOrderDTO $dto): ServiceReturn;

    /**
     * Tài xế hủy chuyến đi (UC-33 Cancel).
     */
    public function cancelOrder(CancelOrderDTO $dto): ServiceReturn;

    /**
     * Bắt đầu thực hiện chuyến đi (UC-35 Start Trip).
     */
    public function startRide(StartRideDTO $dto): ServiceReturn;

    /**
     * Hoàn thành chuyến đi (UC-40 Complete Trip).
     */
    public function completeRide(CompleteRideDTO $dto): ServiceReturn;

    /**
     * Phản hồi yêu cầu hủy chuyến từ khách hàng (UC-28).
     */
    public function respondToCancellation(RespondRideCancellationDTO $dto): ServiceReturn;
}
