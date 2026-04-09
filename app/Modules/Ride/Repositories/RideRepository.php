<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Ride;

class RideRepository implements RideRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(array $data): Ride
    {
        return Ride::create($data);
    }

    /**
     * @inheritDoc
     */
    public function find(int $id): ?Ride
    {
        /** @var Ride|null $ride */
        $ride = Ride::find($id);
        return $ride;
    }
}
