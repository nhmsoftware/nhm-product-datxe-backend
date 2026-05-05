<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use App\Modules\User\Model\Enums\DriverGroupType;

final class AssignDriverGroupDTO
{
    public function __construct(
        public readonly string|int     $userId,
        public readonly DriverGroupType $groupType,
    ) {}
}
