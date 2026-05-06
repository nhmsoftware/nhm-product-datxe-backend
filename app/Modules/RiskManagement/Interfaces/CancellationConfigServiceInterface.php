<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\RiskManagement\DTO\CreateCancellationConfigDTO;
use App\Modules\RiskManagement\DTO\UpdateCancellationConfigDTO;

interface CancellationConfigServiceInterface
{
    /**
     * Danh sách cấu hình
     */
    public function listConfigs(array $filters): ServiceReturn;

    /**
     * Tạo mới cấu hình
     */
    public function createConfig(CreateCancellationConfigDTO $dto): ServiceReturn;

    /**
     * Cập nhật cấu hình
     */
    public function updateConfig(string $id, UpdateCancellationConfigDTO $dto): ServiceReturn;

    /**
     * Chi tiết cấu hình
     */
    public function getConfig(string $id): ServiceReturn;

    /**
     * Xóa cấu hình
     */
    public function deleteConfig(string $id): ServiceReturn;

    /**
     * Lấy phí hủy chuyến phù hợp cho chuyến xe
     * 
     * @param int $rideType
     * @param int $minutesUntilPickup
     * @return ServiceReturn (Data: [fee_type, fee_value])
     */
    public function getApplicableFee(int $rideType, int $minutesUntilPickup): ServiceReturn;
}
