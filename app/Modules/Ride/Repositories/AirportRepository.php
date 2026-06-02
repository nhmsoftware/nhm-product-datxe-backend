<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\AirportRepositoryInterface;
use App\Modules\Ride\Model\Airport;
use Illuminate\Support\Collection;

final class AirportRepository extends BaseRepository implements AirportRepositoryInterface
{
    public function getModel(): string
    {
        return Airport::class;
    }

    /**
     * @inheritDoc
     */
    public function getActiveAirports(): Collection
    {
        return $this->getQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
