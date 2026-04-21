<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Support\Collection;

interface DriverGroupRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách tất cả các nhóm tài xế.
     * @return Collection
     */
    public function getAllGroups(): Collection;
}
