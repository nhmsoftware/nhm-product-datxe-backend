<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

final class ApproveDriverDTO
{
    public function __construct(
        public readonly string|int $userId,
        public readonly ?string    $note = null,
    ) {}
}
