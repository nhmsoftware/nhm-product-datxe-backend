<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

enum RideCallStatus: int
{
    case INITIATED = 1;
    case RINGING = 2;
    case CONNECTED = 3;
    case COMPLETED = 4;
    case FAILED = 5;
    case NO_ANSWER = 6;
    case CANCELED = 7;

    public function getLabel(): string
    {
        return match ($this) {
            self::INITIATED => 'Đã khởi tạo cuộc gọi',
            self::RINGING => 'Đang đổ chuông',
            self::CONNECTED => 'Đã kết nối',
            self::COMPLETED => 'Đã kết thúc',
            self::FAILED => 'Không thể thực hiện cuộc gọi',
            self::NO_ANSWER => 'Không phản hồi',
            self::CANCELED => 'Đã hủy cuộc gọi',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::NO_ANSWER, self::CANCELED], strict: true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::INITIATED => in_array($next, [self::RINGING, self::CONNECTED, self::FAILED, self::NO_ANSWER, self::CANCELED], strict: true),
            self::RINGING => in_array($next, [self::CONNECTED, self::FAILED, self::NO_ANSWER, self::CANCELED], strict: true),
            self::CONNECTED => in_array($next, [self::COMPLETED, self::FAILED, self::CANCELED], strict: true),
            default => false,
        };
    }
}
