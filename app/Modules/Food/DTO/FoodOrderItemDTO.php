<?php

declare(strict_types=1);

namespace App\Modules\Food\DTO;

final class FoodOrderItemDTO
{
    /**
     * @param FoodOrderItemOptionDTO[] $options
     */
    public function __construct(
        public readonly int $menuItemId,
        public readonly int $quantity,
        public readonly ?string $notes = null,
        public readonly array $options = [],
    ) {}
}
