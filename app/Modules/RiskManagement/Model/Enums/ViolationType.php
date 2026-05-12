<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Model\Enums;

enum ViolationType: string
{
    case ATTITUDE = 'ATTITUDE'; // Thái độ không phù hợp
    case CANCELLATION = 'CANCELLATION'; // Hủy chuyến nhiều lần
    case INCOMPLETE_TRIP = 'INCOMPLETE_TRIP'; // Không hoàn thành đơn
    case LATE_DELIVERY = 'LATE_DELIVERY'; // Giao hàng chậm
    case FRAUD = 'FRAUD'; // Gian lận
    case SPAM_BOOKING = 'SPAM_BOOKING'; // Spam đặt chuyến
    case VOUCHER_ABUSE = 'VOUCHER_ABUSE'; // Lạm dụng voucher
    case HARASSMENT = 'HARASSMENT'; // Quấy rối
    case OTHER = 'OTHER'; // Lý do khác

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
