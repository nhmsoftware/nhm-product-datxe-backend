<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model\Enums;

enum PaymentMethodType: string
{
    case E_WALLET      = 'e_wallet';
    case BANK_CARD     = 'bank_card';
    case BANK_TRANSFER = 'bank_transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::E_WALLET      => 'Ví điện tử',
            self::BANK_CARD     => 'Thẻ ngân hàng nội địa',
            self::BANK_TRANSFER => 'Chuyển khoản trực tiếp',
        };
    }
}
