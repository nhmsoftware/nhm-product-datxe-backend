<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\RiskManagement\Model\ScheduledRideCancellationConfig;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CancellationConfigRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm kiếm cấu hình với phân trang
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator;

    /**
     * Tìm quy tắc phù hợp nhất cho chuyến xe
     * 
     * @param int $rideType
     * @param int $minutesUntilPickup
     * @return ScheduledRideCancellationConfig|null
     */
    public function findApplicableRule(int $rideType, int $minutesUntilPickup): ?ScheduledRideCancellationConfig;
}
