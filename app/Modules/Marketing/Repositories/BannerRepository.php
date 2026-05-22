<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Marketing\Interfaces\BannerRepositoryInterface;
use App\Modules\Marketing\Model\Banner;
use App\Modules\Marketing\Model\Enums\MarketingItemStatus;

class BannerRepository extends BaseRepository implements BannerRepositoryInterface
{
    public function getModel(): string
    {
        return Banner::class;
    }

    public function getActiveBanners()
    {
        return $this->model
            ->where('status', MarketingItemStatus::ACTIVE->value)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'desc')
            ->get();
    }
}
