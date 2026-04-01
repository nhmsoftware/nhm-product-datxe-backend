<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs\Auth;

use Modules\User\Domain\Enums\UserOtpType;

final class VerifyOtpDTO
{
    public function __construct(
        public readonly string      $phone,
        public readonly string      $otp,
        public readonly UserOtpType $type,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone: $data['phone'],
            otp:   $data['otp'],
            type:  UserOtpType::from((int) $data['type']),
        );
    }
}
