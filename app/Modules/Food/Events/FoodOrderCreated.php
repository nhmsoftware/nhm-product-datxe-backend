<?php

declare(strict_types=1);

namespace App\Modules\Food\Events;

final class FoodOrderCreated
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly string $merchantId,
        public readonly float $totalPrice,
    ) {}
}
