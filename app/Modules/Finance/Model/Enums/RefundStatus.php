<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

enum RefundStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case COMPLETED = 'COMPLETED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Đang chờ xử lý',
            self::APPROVED => 'Đã phê duyệt',
            self::REJECTED => 'Đã từ chối',
            self::COMPLETED => 'Đã hoàn tiền',
        };
    }
}
