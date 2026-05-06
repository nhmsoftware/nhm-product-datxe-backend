<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface cho FraudAlertRepository.
 */
interface FraudAlertRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách cảnh báo có phân trang và lọc.
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listAlerts(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy thống kê tổng quan về gian lận.
     * 
     * @return array
     */
    public function getFraudStatistics(): array;
}
