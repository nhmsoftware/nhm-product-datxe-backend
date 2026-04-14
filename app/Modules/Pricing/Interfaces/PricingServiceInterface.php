<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\PricingRequestDTO;

interface PricingServiceInterface
{
    /**
     * Calculate the price based on distance, duration, and vehicle type.
     *
     * @param PricingRequestDTO $dto
     * @return ServiceReturn Trả về PricingResultDTO nếu thành công.
     */
    public function calculatePrice(PricingRequestDTO $dto): ServiceReturn;
}
