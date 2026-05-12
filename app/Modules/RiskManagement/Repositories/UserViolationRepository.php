<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\RiskManagement\Interfaces\UserViolationRepositoryInterface;
use App\Modules\RiskManagement\Model\UserViolation;

final class UserViolationRepository extends BaseRepository implements UserViolationRepositoryInterface
{
    public function getModel(): string
    {
        return UserViolation::class;
    }

    public function getByUserId(string $userId)
    {
        return $this->model->where('user_id', $userId)->latest()->get();
    }

    public function countByUserId(string $userId): int
    {
        return $this->model->where('user_id', $userId)->count();
    }
}
