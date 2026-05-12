<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Listeners;

use App\Modules\Pricing\Events\PricingConfigUpdated;
use App\Modules\Pricing\Model\PricingConfigHistory;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener tự động lưu lịch sử khi cấu hình giá thay đổi (UC-125).
 */
final class LogPricingConfigHistory implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PricingConfigUpdated $event): void
    {
        PricingConfigHistory::create([
            'vehicle_type' => $event->vehicleType,
            'old_config'   => $event->oldConfig,
            'new_config'   => $event->newConfig,
            'admin_id'     => $event->adminId,
        ]);
    }
}
