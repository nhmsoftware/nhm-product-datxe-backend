<?php

declare(strict_types=1);

namespace App\Modules\Driver\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\AcceptOrderDTO;
use App\Modules\Driver\DTO\CancelOrderDTO;
use App\Modules\Driver\DTO\RejectOrderDTO;
use App\Modules\Driver\DTO\ToggleOnlineStatusDTO;

interface DriverOperationServiceInterface
{
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
}
