<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Merchant\Interfaces\MerchantMenuEditLogRepositoryInterface;
use App\Modules\Merchant\Model\MerchantMenuEditLog;
use Illuminate\Support\Collection;

final class MerchantMenuEditLogRepository extends BaseRepository implements MerchantMenuEditLogRepositoryInterface
{
    public function getModel(): string
    {
        return MerchantMenuEditLog::class;
    }

    public function getLogsByMerchant(string $merchantProfileId): Collection
    {
        return $this->getQuery()
            ->where('merchant_profile_id', $merchantProfileId)
            ->with(['actor', 'actor.customerProfile'])
            ->latest()
            ->get();
    }

    public function logAction(array $data)
    {
        return $this->create($data);
    }
}
