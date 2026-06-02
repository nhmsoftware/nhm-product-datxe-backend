<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

/**
 * UC-45: Domain Event phát ra khi Driver nạp tiền vào ví tín dụng thành công.
 * Listener sẽ broadcast qua Redis → finance.events → Socket.io → App Tài xế.
 */
final class TopUpCompleted
{
    public function __construct(
        public readonly string $topUpId,
        public readonly string $userId,
        public readonly float  $amount,
        public readonly float  $balanceAfter,
        public readonly string $paymentMethod,
    ) {}
}
