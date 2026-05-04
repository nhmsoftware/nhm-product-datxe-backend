<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\TopUp;

interface TopUpRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find top up by external ID (from gateway)
     */
    public function findByExternalId(string $externalId): ?TopUp;
}
