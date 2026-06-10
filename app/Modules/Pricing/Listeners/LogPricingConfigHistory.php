<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Listeners;

use App\Modules\Pricing\Events\PricingConfigUpdated;
use App\Modules\Pricing\Interfaces\PricingConfigHistoryRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener tự động lưu lịch sử khi cấu hình giá thay đổi (UC-125).
 */
final class LogPricingConfigHistory implements ShouldQueue
{
    public function __construct(
        private readonly PricingConfigHistoryRepositoryInterface $pricingConfigHistoryRepository
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PricingConfigUpdated $event): void
    {
        $this->pricingConfigHistoryRepository->create([
            'vehicle_type' => $event->vehicleTypeId,
            'vehicle_type_id' => $event->vehicleTypeId,
            'old_config'   => $event->oldConfig,
            'new_config'   => $event->newConfig,
            'admin_id'     => $event->adminId,
        ]);
    }
}
