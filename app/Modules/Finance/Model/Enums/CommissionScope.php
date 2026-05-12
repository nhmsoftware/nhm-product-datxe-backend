<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

/**
 * Phạm vi áp dụng của quy tắc hoa hồng.
 */
enum CommissionScope: int
{
    case SYSTEM   = 1; // Toàn hệ thống
    case REGIONAL = 2; // Theo khu vực
    case SERVICE  = 3; // Theo loại dịch vụ (thực tế service_type đã tách riêng, scope này có thể dùng để override)

    public function getLabel(): string
    {
        return match ($this) {
            self::SYSTEM   => 'Toàn hệ thống',
            self::REGIONAL => 'Theo khu vực',
            self::SERVICE  => 'Theo loại dịch vụ',
        };
    }
}
