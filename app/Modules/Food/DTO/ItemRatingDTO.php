<?php

declare(strict_types=1);

namespace App\Modules\Food\DTO;

final class ItemRatingDTO
{
    public function __construct(
        public readonly int $menuItemId,
        public readonly int $rating,
        public readonly ?string $comment = null,
    ) {}
}
