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
     * Lấy danh sách sân bay hoạt động.
     * Nếu có truyền tọa độ, sắp xếp theo khoảng cách từ gần đến xa.
     *
     * @param float|null $lat
     * @param float|null $lng
     * @return Collection
     */
    public function getActiveAirports(?float $lat = null, ?float $lng = null): Collection;
}
