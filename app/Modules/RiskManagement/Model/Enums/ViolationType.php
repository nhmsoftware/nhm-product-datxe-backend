<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

enum ViolationType: int
{
    case ATTITUDE = 1; // Thái độ không phù hợp
    case CANCELLATION = 2; // Hủy chuyến nhiều lần
    case INCOMPLETE_TRIP = 3; // Không hoàn thành đơn
    case LATE_DELIVERY = 4; // Giao hàng chậm
    case FRAUD = 5; // Gian lận
    case SPAM_BOOKING = 6; // Spam đặt chuyến
    case VOUCHER_ABUSE = 7; // Lạm dụng voucher
    case HARASSMENT = 8; // Quấy rối
    case OTHER = 9; // Lý do khác

    public function getLabel(): string
    {
        return match ($this) {
            self::ATTITUDE => 'Thái độ không phù hợp',
            self::CANCELLATION => 'Hủy chuyến nhiều lần',
            self::INCOMPLETE_TRIP => 'Không hoàn thành đơn',
            self::LATE_DELIVERY => 'Giao hàng chậm',
            self::FRAUD => 'Gian lận',
            self::SPAM_BOOKING => 'Spam đặt chuyến',
            self::VOUCHER_ABUSE => 'Lạm dụng voucher',
            self::HARASSMENT => 'Quấy rối',
            self::OTHER => 'Lý do khác',
        };
    }
}
