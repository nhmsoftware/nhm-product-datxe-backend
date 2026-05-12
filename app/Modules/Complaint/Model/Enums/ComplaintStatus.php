<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Model\Enums;

enum ComplaintStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case RESOLVED = 'RESOLVED';
    case REJECTED = 'REJECTED';
    case WAITING_FOR_INFO = 'WAITING_FOR_INFO';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Đang chờ',
            self::PROCESSING => 'Đang xử lý',
            self::RESOLVED => 'Đã giải quyết',
            self::REJECTED => 'Đã từ chối',
            self::WAITING_FOR_INFO => 'Chờ thêm thông tin',
        };
    }
}
