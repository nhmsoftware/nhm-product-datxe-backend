<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Support\Collection;

interface MerchantMenuEditLogRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get edit logs for a specific merchant.
     *
     * @param string $merchantProfileId
     * @return Collection
     */
    public function getLogsByMerchant(string $merchantProfileId): Collection;

    /**
     * Create an edit log entry.
     *
     * @param array $data
     * @return mixed
     */
    public function logAction(array $data);
}
