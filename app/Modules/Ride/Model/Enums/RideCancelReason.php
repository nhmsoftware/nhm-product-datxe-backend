<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

/**
 * Lý do hủy chuyến của tài xế (UC-33).
 */
enum RideCancelReason: int
{
    case CUSTOMER_NO_SHOW = 1; // Khách không ra
    case VEHICLE_BROKEN   = 2; // Xe hỏng
    case WRONG_LOCATION   = 3; // Đặt sai điểm
    case OTHER            = 4; // Khác

    public function getLabel(): string
    {
        return match ($this) {
            self::CUSTOMER_NO_SHOW => 'Khách không ra',
            self::VEHICLE_BROKEN   => 'Xe hỏng',
            self::WRONG_LOCATION   => 'Đặt sai điểm',
            self::OTHER            => 'Khác',
        };
    }
}
