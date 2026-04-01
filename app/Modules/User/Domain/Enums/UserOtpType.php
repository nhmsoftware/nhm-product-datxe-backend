<?php

declare(strict_types=1);

namespace Modules\User\Domain\Enums;

enum UserOtpType: int
{
    case Verify_Register         = 1;
    case Verify_Forgot_Password  = 2;
}
