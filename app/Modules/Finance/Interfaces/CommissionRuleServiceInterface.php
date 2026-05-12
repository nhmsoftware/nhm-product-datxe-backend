<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ConfigureCommissionDTO;
use App\Modules\Finance\Model\Enums\CommissionServiceType;

interface CommissionRuleServiceInterface
{
    /**
     * Cấu hình hoa hồng mới (UC-97).
     * 
     * @param ConfigureCommissionDTO $dto
     * @return ServiceReturn
     */
    public function configure(ConfigureCommissionDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách cấu hình hiện tại.
     * 
     * @return ServiceReturn
     */
    public function listConfigs(): ServiceReturn;

    /**
     * Lấy tỷ lệ hoa hồng áp dụng cho một đơn hàng cụ thể.
     * 
     * @param CommissionServiceType $serviceType
     * @param string|null $areaId
     * @return ServiceReturn
     */
    public function getApplicableCommission(CommissionServiceType $serviceType, ?string $areaId = null): ServiceReturn;

    /**
     * Xóa/Hủy kích hoạt rule.
     * 
     * @param string $ruleId
     * @return ServiceReturn
     */
    public function deleteRule(string $ruleId): ServiceReturn;
}
