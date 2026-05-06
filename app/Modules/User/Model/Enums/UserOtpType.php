<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum UserOtpType: int
{
    use EnumHelper;

    case VERIFY_REGISTER = 1;
    case VERIFY_LOGIN = 2;
    case VERIFY_FORGOT_PASSWORD = 3;
    case CHANGE_PROFILE = 4;
    case VERIFY_DRIVER_REGISTER = 5;
    case VERIFY_MERCHANT_REGISTER = 6;

    public function label(): string
    {
        return match ($this) {
            self::VERIFY_REGISTER        => 'Xác nhận đăng ký',
            self::VERIFY_LOGIN           => 'Xác nhận đăng nhập',
            self::VERIFY_FORGOT_PASSWORD => 'Xác nhận quên mật khẩu',
            self::CHANGE_PROFILE         => 'Xác nhận thay đổi thông tin',
            self::VERIFY_DRIVER_REGISTER => 'Xác nhận đăng ký tài xế',
            self::VERIFY_MERCHANT_REGISTER => 'Xác nhận đăng ký quán ăn',
        };
    }
}
