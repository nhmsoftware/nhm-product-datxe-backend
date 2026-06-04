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
    public function getActiveAirports(?float $lat = null, ?float $lng = null): Collection
    {
        $query = $this->getQuery()->where('is_active', true);

        if ($lat !== null && $lng !== null) {
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat))))";
            
            $query->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
                  ->orderBy('distance');
        } else {
            $query->orderBy('name');
        }

        return $query->get();
    }
}
