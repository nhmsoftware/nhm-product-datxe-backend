<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\UpdateScheduledPricingDTO;

interface ScheduledPricingServiceInterface
{
    /**
     * Lấy cấu hình giá và phân phối hiện tại
     */
    public function getCurrentSettings(): ServiceReturn;

    /**
     * Cập nhật cấu hình giá và phân phối
     */
    public function updateSettings(UpdateScheduledPricingDTO $dto): ServiceReturn;
}
