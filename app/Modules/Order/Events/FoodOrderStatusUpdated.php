<?php

declare(strict_types=1);

namespace App\Modules\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FoodOrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $newStatus,
        public int $oldStatus,
        public ?string $reason = null,
        public ?string $driverId = null // Tài xế cần được notify (khi nhà hàng hủy đơn có tài xế)
    ) {}
}
