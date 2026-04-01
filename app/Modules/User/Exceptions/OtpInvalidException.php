<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

class OtpInvalidException extends UserException
{
    public function __construct()
    {
        parent::__construct('Mã OTP không đúng.');
    }
}
