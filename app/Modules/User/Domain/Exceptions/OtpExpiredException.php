<?php
declare(strict_types=1);
namespace Modules\User\Domain\Exceptions;
class OtpExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Mã OTP đã hết hạn.');
    }
}
