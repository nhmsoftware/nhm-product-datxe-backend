<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs\Auth;

use Modules\User\Domain\Enums\UserOtpType;

final class SendOtpDTO
{
    public function __construct(
        public readonly string      $phone,
        public readonly UserOtpType $type,
        public readonly ?string     $ipAddress = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            phone:     $data['phone'],
            type:      UserOtpType::from((int) $data['type']),
            ipAddress: $data['ip_address'] ?? null,
        );
    }
}
