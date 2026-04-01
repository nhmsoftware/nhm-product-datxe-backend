<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs\Auth;

use Modules\User\Domain\Enums\UserRole;

final class RegisterDTO
{
    public function __construct(
        public readonly string   $phone,
        public readonly string   $password,
        public readonly string   $fullName,
        public readonly UserRole $role,
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
