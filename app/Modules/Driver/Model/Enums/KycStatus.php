<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model\Enums;

enum KycStatus: int
{
    case PENDING  = 1;
    case APPROVED = 2;
    case REJECTED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING  => 'Chờ duyệt',
            self::APPROVED => 'Đã duyệt',
            self::REJECTED => 'Từ chối',
        };
    }

    public function isPending(): bool  { return $this === self::PENDING; }
    public function isApproved(): bool { return $this === self::APPROVED; }
    public function isRejected(): bool { return $this === self::REJECTED; }

    public function isTerminal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED], strict: true);
    }
}
