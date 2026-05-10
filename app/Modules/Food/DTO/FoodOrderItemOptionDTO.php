<?php

declare(strict_types=1);

namespace App\Modules\Food\DTO;

final class FoodOrderItemOptionDTO
{
    public function __construct(
        public readonly string $optionName,
        public readonly string $optionValue,
        public readonly float $price = 0,
    ) {}
}
