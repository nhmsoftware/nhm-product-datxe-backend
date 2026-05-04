<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

enum WalletTransactionType: int
{
    case EARNINGS   = 1;
    case TOP_UP     = 2;
    case FEE        = 3;
    case WITHDRAWAL = 4;
    case REFUND     = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::EARNINGS   => 'Thu nhập từ chuyến đi',
            self::TOP_UP     => 'Nạp tiền vào ví',
            self::FEE        => 'Trừ phí dịch vụ',
            self::WITHDRAWAL => 'Rút tiền',
            self::REFUND     => 'Hoàn tiền',
        };
    }

    public function getSymbol(): string
    {
        return match ($this) {
            self::EARNINGS, self::TOP_UP, self::REFUND => '+',
            self::FEE, self::WITHDRAWAL => '-',
        };
    }
}
