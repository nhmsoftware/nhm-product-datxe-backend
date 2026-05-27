<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

/**
 * Phân loại loại hình chuyến xe.
 */
enum RideType: int
{
    case CITY      = 1; // Nội thành
    case INTERCITY = 2; // Đi tỉnh
    case AIRPORT   = 3; // Sân bay
    case DELIVERY  = 4; // Giao hàng
    case CHAUFFEUR = 5; // Lái hộ
    case FOOD_DELIVERY = 6; // Giao đồ ăn

    public function getLabel(): string
    {
        return match ($this) {
            self::CITY          => 'Nội thành',
            self::INTERCITY     => 'Đi tỉnh',
            self::AIRPORT       => 'Sân bay',
            self::DELIVERY      => 'Giao hàng',
            self::CHAUFFEUR     => 'Lái hộ',
            self::FOOD_DELIVERY => 'Giao đồ ăn',
        };
    }
}
