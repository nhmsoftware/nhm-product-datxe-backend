<?php

declare(strict_types=1);

namespace App\Modules\Food\Events;

final class FoodOrderRated
{
    public function __construct(
        public readonly string $ratingId,
        public readonly string $orderId,
        public readonly int $merchantId,
        public readonly int $customerId,
        public readonly int $rating,
        public readonly array $itemsRating = [],
    ) {}
}
