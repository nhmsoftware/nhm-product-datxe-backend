<?php

declare(strict_types=1);

namespace App\Modules\Marketing\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

interface BannerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active banners ordered by their 'order' column
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveBanners();
}
