<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\CommissionRule;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface Repository quản lý quy tắc hoa hồng.
 */
interface CommissionRuleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy quy tắc đang hoạt động cho một loại dịch vụ và khu vực.
     * 
     * @param CommissionServiceType $serviceType
     * @param string|null $areaId
     * @return CommissionRule|null
     */
    public function getActiveRule(CommissionServiceType $serviceType, ?string $areaId = null): ?CommissionRule;

    /**
     * Lấy danh sách tất cả quy tắc (UC-97).
     * 
     * @return Collection
     */
    public function getAllRules(): Collection;

    /**
     * Kiểm tra xem có quy tắc nào bị trùng khoảng thời gian không.
     * 
     * @param CommissionServiceType $serviceType
     * @param int $scope
     * @param string|null $areaId
     * @param string $from
     * @param string|null $to
     * @param string|null $excludeId
     * @return bool
     */
    public function hasOverlappingRule(
        CommissionServiceType $serviceType,
        int                   $scope,
        ?string               $areaId,
        string                $from,
        ?string               $to = null,
        ?string               $excludeId = null
    ): bool;
}
