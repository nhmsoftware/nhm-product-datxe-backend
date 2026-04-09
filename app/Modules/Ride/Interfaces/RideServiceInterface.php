<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Services\ServiceReturn;

interface RideServiceInterface
{
    /**
     * Create a draft ride booking (UC-08).
     * 
     * @param array $data
     * @return ServiceReturn
     */
    public function createDraft(array $data): ServiceReturn;
}
