<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

enum TopUpStatus: string
{
    case PENDING   = 'pending';
    case SUCCESS   = 'success';
    case FAILED    = 'failed';
    case CANCELLED = 'cancelled';
    case EXPIRED   = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING   => 'Đang xử lý',
            self::SUCCESS   => 'Thành công',
            self::FAILED    => 'Thất bại',
            self::CANCELLED => 'Đã hủy',
            self::EXPIRED   => 'Đã hết hạn',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::SUCCESS, self::FAILED, self::CANCELLED, self::EXPIRED => true,
            self::PENDING                                               => false,
        };
    }
}
