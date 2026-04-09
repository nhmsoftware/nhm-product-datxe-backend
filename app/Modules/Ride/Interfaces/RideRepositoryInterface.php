<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Modules\Ride\Model\Ride;

interface RideRepositoryInterface
{
    /**
     * Create a new ride record.
     * 
     * @param array $data
     * @return Ride
     */
    public function create(array $data): Ride;

    /**
     * Find a ride by ID.
     * 
     * @param int $id
     * @return Ride|null
     */
    public function find(int $id): ?Ride;
}
