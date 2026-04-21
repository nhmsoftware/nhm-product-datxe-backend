<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\DriverGroupRepositoryInterface;
use App\Modules\User\Model\DriverGroup;
use Illuminate\Support\Collection;

final class DriverGroupRepository extends BaseRepository implements DriverGroupRepositoryInterface
{
    public function getModel(): string
    {
        return DriverGroup::class;
    }

    /**
     * @inheritDoc
     */
    public function getAllGroups(): Collection
    {
        return $this->model->all();
    }
}
