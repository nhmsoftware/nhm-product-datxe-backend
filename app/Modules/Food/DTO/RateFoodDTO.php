<?php

declare(strict_types=1);

namespace App\Modules\Food\DTO;

use App\Modules\Food\Http\Requests\RateFoodRequest;

final class RateFoodDTO
{
    /**
     * @param ItemRatingDTO[] $itemsRating
     */
    public function __construct(
        public readonly string $orderId,
        public readonly int $customerId,
        public readonly int $rating,
        public readonly ?string $comment = null,
        public readonly ?int $foodQualityRating = null,
        public readonly ?int $deliveryTimeRating = null,
        public readonly ?int $serviceRating = null,
        public readonly array $itemsRating = [],
    ) {}

    public static function fromRequest(RateFoodRequest $request): self
    {
        $itemsRating = [];
        foreach ($request->input('items', []) as $item) {
            $itemsRating[] = new ItemRatingDTO(
                menuItemId: (int) $item['menu_item_id'],
                rating: (int) $item['rating'],
                comment: $item['comment'] ?? null
            );
        }

        return new self(
            orderId: (string) $request->route('orderId'),
            customerId: (int) $request->user()->id,
            rating: (int) $request->input('rating'),
            comment: $request->input('comment'),
            foodQualityRating: $request->has('food_quality_rating') ? (int) $request->input('food_quality_rating') : null,
            deliveryTimeRating: $request->has('delivery_time_rating') ? (int) $request->input('delivery_time_rating') : null,
            serviceRating: $request->has('service_rating') ? (int) $request->input('service_rating') : null,
            itemsRating: $itemsRating
        );
    }
}
