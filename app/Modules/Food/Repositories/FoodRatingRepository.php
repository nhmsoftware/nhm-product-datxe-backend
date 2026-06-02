<?php

declare(strict_types=1);

namespace App\Modules\Food\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Food\Interfaces\FoodRatingRepositoryInterface;
use App\Modules\Food\Model\FoodRating;
use Illuminate\Support\Facades\DB;

final class FoodRatingRepository extends BaseRepository implements FoodRatingRepositoryInterface
{
    public function getModel(): string
    {
        return FoodRating::class;
    }

    public function saveRating(array $ratingData, array $itemsRatingData): FoodRating
    {
        /** @var FoodRating $rating */
        $rating = $this->getQuery()->create($ratingData);

        foreach ($itemsRatingData as $itemData) {
            $rating->itemRatings()->create($itemData);
        }

        return $rating;
    }

    public function isOrderRated(string $orderId): bool
    {
        return $this->getQuery()->where('food_order_id', $orderId)->exists();
    }
}
