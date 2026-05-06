<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\RiskManagement\DTO\ListFraudAlertsDTO;

/**
 * Interface cho AntiFraudService.
 */
interface AntiFraudServiceInterface
{
    /**
     * Lấy dữ liệu tổng quan hệ thống chống gian lận.
     * 
     * @return ServiceReturn
     */
    public function getOverview(): ServiceReturn;

    /**
     * Danh sách cảnh báo gian lận.
     * 
     * @param ListFraudAlertsDTO $dto
     * @return ServiceReturn
     */
    public function listAlerts(ListFraudAlertsDTO $dto): ServiceReturn;

    /**
     * Xem chi tiết cảnh báo.
     * 
     * @param string|int $id
     * @return ServiceReturn
     */
    public function getDetail(string|int $id): ServiceReturn;
}
