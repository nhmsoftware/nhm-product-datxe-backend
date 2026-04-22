<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\RegisterSubscriptionDTO;

interface SubscriptionServiceInterface
{
    /**
     * Get list of available subscription packages (UC-46)
     */
    public function getAvailablePackages(): ServiceReturn;

    /**
     * Register a subscription package for driver (UC-46)
     */
    public function registerSubscription(RegisterSubscriptionDTO $dto): ServiceReturn;
}
