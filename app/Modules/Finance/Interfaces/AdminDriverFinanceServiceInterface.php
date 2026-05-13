<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\AdminDriverFinanceSummaryDTO;

interface AdminDriverFinanceServiceInterface
{
    /**
     * Lấy dữ liệu tổng quan tài chính tài xế cho Admin.
     * UC-116 Manage Driver Financial Model
     * 
     * @param AdminDriverFinanceSummaryDTO $dto
     * @return ServiceReturn
     */
    public function getSummary(AdminDriverFinanceSummaryDTO $dto): ServiceReturn;
}
