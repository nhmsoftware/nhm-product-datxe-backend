<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

enum ViolationType: int
{
    case EXCESSIVE_CANCELLATION = 1;
    case VOUCHER_FRAUD          = 2;
    case FAKE_ORDER             = 3;
    case UNFINISHED_ORDER       = 4;
    case RIDE_SPAM              = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::EXCESSIVE_CANCELLATION => 'Hủy chuyến quá nhiều',
            self::VOUCHER_FRAUD          => 'Gian lận voucher',
            self::FAKE_ORDER             => 'Tạo đơn ảo',
            self::UNFINISHED_ORDER       => 'Không hoàn thành đơn',
            self::RIDE_SPAM              => 'Spam đặt xe',
        };
    }
}
