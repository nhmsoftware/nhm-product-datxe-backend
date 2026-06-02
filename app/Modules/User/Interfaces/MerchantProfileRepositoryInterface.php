<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface MerchantProfileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm ngẫu nhiên các cửa hàng đang mở cửa và đã được duyệt.
     * @param int $limit
     * @return Collection
     */
    public function getRandomActiveMerchants(int $limit = 5): Collection;
}
