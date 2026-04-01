<?php

declare(strict_types=1);

namespace App\Modules\User\Exceptions;

class UserAlreadyExistsException extends UserException
{
    public function __construct(string $phone)
    {
        parent::__construct("Số điện thoại {$phone} đã được đăng ký.");
    }
}
