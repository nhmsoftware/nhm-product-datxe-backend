<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

final class UpdateDriverStatusDTO
{
    public function __construct(
        public readonly string|int $userId,
        public readonly bool       $isActive,
        public readonly ?string    $lockReason = null,
        public readonly ?int       $lockedDays = null,
    ) {}
}
