<?php

declare(strict_types=1);

namespace App\Modules\Order\Model\Enums;

enum OrderType: string
{
    case FOOD = 'Food';
    case DELIVERY = 'Delivery';
    case RIDE = 'Ride';

    public function getLabel(): string
    {
        return match ($this) {
            self::FOOD => 'Đồ ăn',
            self::DELIVERY => 'Giao hàng',
            self::RIDE => 'Chuyến xe',
        };
    }
}
