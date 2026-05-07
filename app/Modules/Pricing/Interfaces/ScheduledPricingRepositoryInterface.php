<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Pricing\Model\ScheduledPricingConfig;

interface ScheduledPricingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy cấu hình giá hiện tại (chỉ lấy 1 dòng active duy nhất)
     */
    public function getCurrentConfig(): ?ScheduledPricingConfig;
}
