<?php

declare(strict_types=1);

namespace App\Modules\Food\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Food\DTO\CreateFoodOrderDTO;

interface FoodOrderServiceInterface
{
    /**
     * UC-18: Order Food
     * 
     * @param CreateFoodOrderDTO $dto
     * @return ServiceReturn
     */
    public function createOrder(CreateFoodOrderDTO $dto): ServiceReturn;

    /**
     * UC-18: Calculate Estimate for UI
     * 
     * @param CreateFoodOrderDTO $dto
     * @return ServiceReturn
     */
    public function calculateEstimate(CreateFoodOrderDTO $dto): ServiceReturn;
}
