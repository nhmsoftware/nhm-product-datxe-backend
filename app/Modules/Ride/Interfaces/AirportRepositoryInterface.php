<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Interface cho Repository quản lý danh sách sân bay.
 */
interface AirportRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy danh sách các sân bay đang hoạt động.
     *
     * @return Collection
     */
    public function getActiveAirports(): Collection;
}
