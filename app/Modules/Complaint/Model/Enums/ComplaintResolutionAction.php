<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Model\Enums;

enum ComplaintResolutionAction: string
{
    case REFUND = 'REFUND';
    case WARN_DRIVER = 'WARN_DRIVER';
    case WARN_CUSTOMER = 'WARN_CUSTOMER';
    case REJECT = 'REJECT';
    case REQUEST_INFO = 'REQUEST_INFO';

    public function getLabel(): string
    {
        return match ($this) {
            self::REFUND => 'Hoàn tiền',
            self::WARN_DRIVER => 'Cảnh báo tài xế',
            self::WARN_CUSTOMER => 'Cảnh báo khách hàng',
            self::REJECT => 'Từ chối khiếu nại',
            self::REQUEST_INFO => 'Yêu cầu thêm thông tin',
        };
    }
}
