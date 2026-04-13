<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

use App\Core\Services\ServiceReturn;

use App\Modules\Ride\DTO\CreateDraftRideDTO;

interface RideServiceInterface
{
    /**
     * Create a draft ride booking (UC-08).
     * 
     * @param CreateDraftRideDTO $dto Thông tin chuyến xe nháp
     * @return ServiceReturn
     */
    public function createDraft(CreateDraftRideDTO $dto): ServiceReturn;
}
