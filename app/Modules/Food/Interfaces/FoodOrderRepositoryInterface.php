<?php

declare(strict_types=1);

namespace App\Modules\Food\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Food\Model\FoodOrder;

interface FoodOrderRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * UC-18: Create food order with items and options
     */
    public function createOrder(array $orderData, array $itemsData): FoodOrder;
}
