<?php

declare(strict_types=1);

namespace App\Modules\Auth\DTO;

use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\User\Model\Enums\Gender;
use App\Modules\User\Model\Enums\UserRole;

/**
 * DTO cho request đăng ký tài khoản mới (UC-01).
 */
final class RegisterDTO
{
    public function __construct(
        public readonly string   $phone,
        public readonly string   $otp,
        public readonly string   $fullName,
        public readonly string   $password,
        public readonly UserRole $role,
        public readonly ?string  $deviceId    = null,
        public readonly ?string  $deviceToken = null,
        public readonly ?string  $deviceType  = null,
    ) {
    }

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            phone:       $request->string('phone')->toString(),
            otp:         $request->string('otp')->toString(),
            fullName:    $request->string('full_name')->toString(),
            password:    $request->string('password')->toString(),
            role:        UserRole::from((int) $request->input('role', UserRole::Customer->value)),
            deviceId:    $request->input('device_id'),
            deviceToken: $request->input('device_token'),
            deviceType:  $request->input('device_type'),
        );
    }
}
