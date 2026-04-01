<?php
declare(strict_types=1);
namespace Modules\User\Domain\Exceptions;
class AuthenticationFailedException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Số điện thoại hoặc mật khẩu không đúng.');
    }
}
