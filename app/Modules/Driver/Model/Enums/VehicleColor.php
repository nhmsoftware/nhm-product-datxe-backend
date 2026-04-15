<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model\Enums;

enum VehicleColor: int
{
    case OTHER  = 0;
    case RED    = 1;
    case GREEN  = 2;
    case BLUE   = 3;
    case YELLOW = 4;
    case ORANGE = 5;
    case PURPLE = 6;
    case BROWN  = 7;
    case BLACK  = 8;
    case WHITE  = 9;

    public function getLabel(): string
    {
        return match ($this) {
            self::OTHER  => 'Khác',
            self::RED    => 'Đỏ',
            self::GREEN  => 'Xanh lá',
            self::BLUE   => 'Xanh dương',
            self::YELLOW => 'Vàng',
            self::ORANGE => 'Cam',
            self::PURPLE => 'Tím',
            self::BROWN  => 'Nâu',
            self::BLACK  => 'Đen',
            self::WHITE  => 'Trắng',
        };
    }
}
