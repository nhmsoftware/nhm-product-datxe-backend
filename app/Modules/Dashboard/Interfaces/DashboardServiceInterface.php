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
}
