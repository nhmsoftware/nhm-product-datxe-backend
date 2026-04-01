<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

class AuthenticationFailedException extends UserException
{
    public function __construct()
    {
        parent::__construct('Số điện thoại hoặc mật khẩu không đúng.');
    }
}
