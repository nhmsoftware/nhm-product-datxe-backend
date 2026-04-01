<?php
declare(strict_types=1);
namespace Modules\User\Domain\Exceptions;
class UserAlreadyExistsException extends DomainException
{
    public function __construct(string $phone)
    {
        parent::__construct("Số điện thoại {$phone} đã được đăng ký.");
    }
}
