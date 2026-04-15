<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;

/**
 * DTO cho request đặt lại mật khẩu (UC-05).
 */
final class ForgotPasswordDTO
{
    public function __construct(
        public readonly string  $phone,
        public readonly string  $otp,
        public readonly string  $password,
        public readonly ?string $deviceId    = null,
        public readonly ?string $deviceToken = null,
        public readonly ?string $deviceType  = null,
    ) {
    }

    public static function fromRequest(ForgotPasswordRequest $request): self
    {
        return new self(
            phone:       $request->string('phone')->toString(),
            otp:         $request->string('otp')->toString(),
            password:    $request->string('password')->toString(),
            deviceId:    $request->input('device_id'),
            deviceToken: $request->input('device_token'),
            deviceType:  $request->input('device_type'),
        );
    }
}
