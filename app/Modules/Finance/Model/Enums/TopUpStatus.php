<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

enum TopUpStatus: string
{
    case PENDING   = 'pending';
    case SUCCESS   = 'success';
    case FAILED    = 'failed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING   => 'Đang xử lý',
            self::SUCCESS   => 'Thành công',
            self::FAILED    => 'Thất bại',
            self::CANCELLED => 'Đã hủy',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::SUCCESS, self::FAILED, self::CANCELLED => true,
            self::PENDING                                => false,
        };
    }
}
