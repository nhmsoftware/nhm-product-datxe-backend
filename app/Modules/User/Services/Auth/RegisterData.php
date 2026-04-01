<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

use App\Modules\User\Model\Enums\UserRole;

final class RegisterData
{
    public function __construct(
        public readonly string   $phone,
        public readonly string   $password,
        public readonly string   $fullName,
        public readonly UserRole $role       = UserRole::Customer,
        public readonly ?string  $deviceId   = null,
        public readonly ?string  $deviceToken = null,
        public readonly ?string  $deviceType  = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone:       $data['phone'],
            password:    $data['password'],
            fullName:    $data['full_name'],
            role:        UserRole::from((int) ($data['role'] ?? UserRole::Customer->value)),
            deviceId:    $data['device_id']    ?? null,
            deviceToken: $data['device_token'] ?? null,
            deviceType:  $data['device_type']  ?? null,
        );
    }
}
