<?php

declare(strict_types=1);

namespace App\Modules\User\Model\Enums;

use App\Core\Traits\EnumHelper;

enum UserOtpType: int
{
    use EnumHelper;
    case Verify_Register        = 1;
    case Verify_Forgot_Password = 2;

    public function label(): string
    {
        return match ($this){
            self::Verify_Register        => 'Xác nhận đăng ký',
            self::Verify_Forgot_Password => 'Xác nhận quên mật khẩu',
        };
    }
}
