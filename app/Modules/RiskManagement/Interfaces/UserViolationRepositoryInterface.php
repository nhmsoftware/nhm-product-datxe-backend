<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface UserViolationRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUserId(string $userId);

    public function countByUserId(string $userId): int;
}
