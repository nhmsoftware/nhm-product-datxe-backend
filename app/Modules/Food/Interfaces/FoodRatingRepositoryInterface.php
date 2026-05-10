<?php

declare(strict_types=1);

namespace App\Modules\Food\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Food\Model\FoodRating;

interface FoodRatingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * UC-20: Save food rating with item details
     */
    public function saveRating(array $ratingData, array $itemsRatingData): FoodRating;

    /**
     * Check if an order has been rated
     */
    public function isOrderRated(string $orderId): bool;
}
