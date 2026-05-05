<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum KycStatus: int
{
    use EnumHelper;

    case Pending  = 1;
    case Approved = 2;
    case Rejected = 3;

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
        };
    }
}
