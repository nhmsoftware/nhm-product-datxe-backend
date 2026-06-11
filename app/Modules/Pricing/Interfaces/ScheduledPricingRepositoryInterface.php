<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Pricing\Model\ScheduledPricingConfig;

interface ScheduledPricingRepositoryInterface
{
    /**
     * Lấy cấu hình giá hiện tại (bao gồm phụ phí và rules + ranges)
     */
    public function getCurrentConfig(): array;

    /**
     * Lưu cấu hình giá mới
     */
    public function saveConfig(array $surchargeData, array $rulesData): array;

    /**
     * Tìm rule giá đặt trước phù hợp theo ngữ cảnh runtime.
     */
    public function findMatchingRule(
        int $serviceType,
        string $rideMode,
        int $vehicleTypeId,
        ?string $airportId = null
    ): ?\App\Modules\Pricing\Model\ScheduledPricingRule;
}
