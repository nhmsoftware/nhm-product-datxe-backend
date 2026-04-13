<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use App\Modules\Auth\Http\Requests\SendOtpRequest;
use App\Modules\User\Model\Enums\UserOtpType;

/**
 * DTO cho request gửi OTP.
 */
final class SendOtpDTO
{
    public function __construct(
        public readonly string      $phone,
        public readonly UserOtpType $type,
    ) {
    }

    public static function fromRequest(SendOtpRequest $request): self
    {
        return new self(
            phone: $request->string('phone')->toString(),
            type:  UserOtpType::from((int) $request->input('type')),
        );
    }
}
