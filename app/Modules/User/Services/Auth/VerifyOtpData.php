<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

use App\Modules\User\Model\Enums\UserOtpType;

final class VerifyOtpData
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
