<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\Interfaces;

use App\Modules\Ride\Model\Ride;

/**
 * Interface cho Chauffeur Repository.
 */
interface ChauffeurRepositoryInterface
{
    /**
     * Tạo một bản ghi Ride với các thông tin lái hộ.
     *
     * @param array $data
     * @return Ride
     */
    public function createChauffeurRide(array $data): Ride;
}
