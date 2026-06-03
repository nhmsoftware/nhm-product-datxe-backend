<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\MerchantProfileRepositoryInterface;
use App\Modules\User\Model\MerchantProfile;
use App\Modules\User\Model\Enums\KycStatus;
use Illuminate\Database\Eloquent\Collection;

class MerchantProfileRepository extends BaseRepository implements MerchantProfileRepositoryInterface
{
    public function getModel(): string
    {
        return MerchantProfile::class;
    }

    public function getRandomActiveMerchants(int $limit = 5): Collection
    {
        return $this->getQuery()
            ->where('status', KycStatus::Approved)
            ->where('is_open', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
