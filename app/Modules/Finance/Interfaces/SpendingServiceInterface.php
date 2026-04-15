<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ViewSpendingSummaryDTO;

interface SpendingServiceInterface
{
    /**
     * Lấy thông tin tổng hợp chi tiêu của khách hàng (UC-23).
     *
     * @param ViewSpendingSummaryDTO $dto
     * @return ServiceReturn
     */
    public function getSummary(ViewSpendingSummaryDTO $dto): ServiceReturn;
}
