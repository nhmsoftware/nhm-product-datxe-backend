<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Interfaces;

use App\Core\Services\ServiceReturn;

interface DashboardServiceInterface
{
    /**
     * Lấy dữ liệu thống kê cho Dashboard (UC-76).
     */
    public function getDashboardStats(): ServiceReturn;

    /**
     * Báo cáo doanh thu theo thời gian.
     */
    public function getRevenueReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn;

    /**
     * Báo cáo theo khu vực.
     */
    public function getAreaReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn;

    /**
     * Báo cáo hoa hồng.
     */
    public function getCommissionReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn;

    /**
     * Báo cáo quản lý đơn hàng.
     */
    public function getOrderReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn;

    /**
     * Báo cáo chi tiết theo loại xe và dịch vụ.
     */
    public function getDetailedReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn;

    /**
     * Báo cáo top tài xế theo doanh thu.
     */
    public function getTopDriversReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn;
}
