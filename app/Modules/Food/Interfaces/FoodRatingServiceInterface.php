<?php

declare(strict_types=1);

namespace App\Modules\Food\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Food\DTO\RateFoodDTO;

interface FoodRatingServiceInterface
{
    /**
     * UC-20: Rate Food Order
     */
    public function rateOrder(RateFoodDTO $dto): ServiceReturn;
}
