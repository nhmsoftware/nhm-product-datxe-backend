<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserWarned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $violationId,
        public readonly string $type,
        public readonly string $reason,
        public readonly int $violationCount,
        public readonly ?string $adminId = null,
    ) {}
}
