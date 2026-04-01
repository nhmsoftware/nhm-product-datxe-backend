<?php

declare(strict_types=1);

namespace App\Modules\User\Services\Auth;

final class LoginData
{
    public function __construct(
        public readonly string  $phone,
        public readonly string  $password,
        public readonly ?string $deviceId    = null,
        public readonly ?string $deviceToken = null,
        public readonly ?string $deviceType  = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone:       $data['phone'],
            password:    $data['password'],
            deviceId:    $data['device_id']    ?? null,
            deviceToken: $data['device_token'] ?? null,
            deviceType:  $data['device_type']  ?? null,
        );
    }
}
