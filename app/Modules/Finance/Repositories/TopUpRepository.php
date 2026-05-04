<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\TopUpRepositoryInterface;
use App\Modules\Finance\Model\TopUp;

final class TopUpRepository extends BaseRepository implements TopUpRepositoryInterface
{
    public function getModel(): string
    {
        return TopUp::class;
    }

    public function findByExternalId(string $externalId): ?TopUp
    {
        /** @var TopUp|null */
        return $this->model->where('external_id', $externalId)->first();
    }
}
