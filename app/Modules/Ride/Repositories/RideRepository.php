<?php

declare(strict_types=1);

namespace App\Modules\Ride\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Ride;

class RideRepository extends BaseRepository implements RideRepositoryInterface
{
    public function getModel(): string
    {
        return Ride::class;
    }
}
