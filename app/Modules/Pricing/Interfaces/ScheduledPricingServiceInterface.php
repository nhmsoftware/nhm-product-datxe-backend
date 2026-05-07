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

    /**
     * Toggle chế độ phân phối:
     *   - admin_priority (bật): Admin giữ quyền gán tài xế thủ công.
     *   - open_pool (tắt): Tự động đẩy chuyến vào pool cho tài xế nhận.
     * Khi chuyển sang open_pool → tự động push toàn bộ chuyến đang chờ.
     */
    public function toggleDispatchMode(int $mode): ServiceReturn;
}
