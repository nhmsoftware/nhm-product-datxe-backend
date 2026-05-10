<?php

declare(strict_types=1);

namespace App\Modules\Food\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Food\Interfaces\FoodOrderRepositoryInterface;
use App\Modules\Food\Model\FoodOrder;
use Illuminate\Support\Facades\DB;

final class FoodOrderRepository extends BaseRepository implements FoodOrderRepositoryInterface
{
    public function getModel(): string
    {
        return FoodOrder::class;
    }

    public function createOrder(array $orderData, array $itemsData): FoodOrder
    {
        return DB::transaction(function () use ($orderData, $itemsData) {
            /** @var FoodOrder $order */
            $order = $this->model->create($orderData);

            foreach ($itemsData as $itemData) {
                $options = $itemData['options'] ?? [];
                unset($itemData['options']);

                $item = $order->items()->create($itemData);

                foreach ($options as $option) {
                    $item->options()->create($option);
                }
            }

            return $order->load('items.options');
        });
    }
}
