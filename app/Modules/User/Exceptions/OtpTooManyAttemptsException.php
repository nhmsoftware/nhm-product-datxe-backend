<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

class OtpTooManyAttemptsException extends UserException
{
    public function __construct()
    {
        parent::__construct('Bạn đã nhập sai OTP quá nhiều lần. Vui lòng yêu cầu mã mới.');
    }
}
