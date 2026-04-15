<?php

declare(strict_types=1);

namespace App\Modules\Driver\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\ToggleOnlineStatusDTO;

interface DriverOperationServiceInterface
{
    /**
     * Bật/Tắt tính năng trực tuyến (nhận chuyến) của tài xế. (UC-31)
     */
    public function toggleOnlineStatus(ToggleOnlineStatusDTO $dto): ServiceReturn;
}
