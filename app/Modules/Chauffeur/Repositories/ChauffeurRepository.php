<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Repositories;

use App\Modules\Chauffeur\Interfaces\ChauffeurRepositoryInterface;
use App\Modules\Ride\Model\Ride;

/**
 * Triển khai Chauffeur Repository.
 */
class ChauffeurRepository implements ChauffeurRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function createChauffeurRide(array $data): Ride
    {
        return Ride::create($data);
    }
}
