<?php

declare(strict_types=1);

namespace App\Modules\Homepage\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\Model\User;

interface HomepageServiceInterface
{
    /**
     * Lấy dữ liệu trang chủ cho người dùng (Guest hoặc Customer).
     *
     * @param User|null $user
     * @param float|null $lat
     * @param float|null $lng
     * @return ServiceReturn
     */
    public function getHomepageData(?User $user, float $lat = null, float $lng = null): ServiceReturn;
}
