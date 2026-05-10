<?php

declare(strict_types=1);

namespace App\Modules\Food\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Food\DTO\RateFoodDTO;
use App\Modules\Food\Events\FoodOrderRated;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;
use App\Modules\Food\Interfaces\FoodRatingRepositoryInterface;
use App\Modules\Food\Interfaces\FoodRatingServiceInterface;
use App\Modules\Food\Model\Enums\FoodOrderStatus;

final class FoodRatingService extends BaseService implements FoodRatingServiceInterface
{
    public function __construct(
        private readonly FoodRatingRepositoryInterface $foodRatingRepository,
        private readonly FoodOrderRepositoryInterface $foodOrderRepository,
    ) {}

    public function rateOrder(RateFoodDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Verify Order
            $order = $this->foodOrderRepository->findById($dto->orderId);
            $this->validate($order !== null, 'Không tìm thấy đơn hàng.', 404);
            $this->validate($order->customer_id === $dto->customerId, 'Bạn không có quyền đánh giá đơn hàng này.', 403);
            
            // 2. Preconditions (UC-20)
            $this->validate($order->status === FoodOrderStatus::DELIVERED, 'Đơn hàng chưa hoàn thành, không thể đánh giá.', 400);
            
            // A5 - Đã đánh giá trước đó
            $this->validate(!$this->foodRatingRepository->isOrderRated($dto->orderId), 'Bạn đã đánh giá đơn hàng này.', 400);

            // 3. Save Rating
            $ratingData = [
                'food_order_id' => $dto->orderId,
                'customer_id' => $dto->customerId,
                'merchant_id' => $order->merchant_id,
                'rating' => $dto->rating,
                'comment' => $dto->comment,
                'food_quality_rating' => $dto->foodQualityRating,
                'delivery_time_rating' => $dto->deliveryTimeRating,
                'service_rating' => $dto->serviceRating,
            ];

            $itemsRatingData = [];
            foreach ($dto->itemsRating as $itemRating) {
                $itemsRatingData[] = [
                    'menu_item_id' => $itemRating->menuItemId,
                    'rating' => $itemRating->rating,
                    'comment' => $itemRating->comment,
                ];
            }

            $rating = $this->foodRatingRepository->saveRating($ratingData, $itemsRatingData);

            // 4. Dispatch Event (Step 8 UC-20)
            event(new FoodOrderRated(
                ratingId: (string) $rating->id,
                orderId: $dto->orderId,
                merchantId: (int) $order->merchant_id,
                customerId: $dto->customerId,
                rating: $dto->rating,
                itemsRating: $itemsRatingData
            ));

            return $this->success($rating->toArray(), 'Cảm ơn bạn đã đánh giá đơn hàng!');
        }, true, 'RateFoodOrder');
    }
}
