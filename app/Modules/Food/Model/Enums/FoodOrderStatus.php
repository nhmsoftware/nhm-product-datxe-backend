<?php

declare(strict_types=1);

namespace App\Modules\Food\Model\Enums;

enum FoodOrderStatus: int
{
    case PENDING = 1;
    case CONFIRMED = 2;
    case PREPARING = 3;
    case READY = 4;
    case PICKED_UP = 5;
    case DELIVERED = 6;
    case CANCELLED = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ xác nhận',
            self::CONFIRMED => 'Đã xác nhận',
            self::PREPARING => 'Đang chế biến',
            self::READY => 'Món đã sẵn sàng',
            self::PICKED_UP => 'Đang giao hàng',
            self::DELIVERED => 'Đã giao thành công',
            self::CANCELLED => 'Đã hủy',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::DELIVERED, self::CANCELLED], true);
    }
}
