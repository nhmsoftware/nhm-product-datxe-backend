<?php
declare(strict_types=1);
namespace Modules\User\Domain\Exceptions;
class OtpTooManyAttemptsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Bạn đã nhập sai OTP quá nhiều lần. Vui lòng yêu cầu mã mới.');
    }
}
