<?php

declare(strict_types=1);

namespace App\Modules\Operation\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Operation\DTO\UpdateLocationDTO;
use App\Modules\Operation\DTO\GetNavigationDTO;

/**
 * Interface cho Service quản lý các hoạt động định vị và dẫn đường.
 */
interface OperationServiceInterface
{
    /**
     * Cập nhật vị trí hiện tại (UC-35).
     */
    public function updateLocation(UpdateLocationDTO $dto): ServiceReturn;

    /**
     * Lấy dữ liệu dẫn đường cho một chuyến xe (UC-34).
     */
    public function getNavigation(GetNavigationDTO $dto): ServiceReturn;
}
