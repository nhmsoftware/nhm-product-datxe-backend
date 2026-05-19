<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\GetNearbyMerchantsDTO;

interface CustomerMerchantServiceInterface
{
    /**
     * Get nearby merchants based on customer location.
     *
     * @param GetNearbyMerchantsDTO $dto
     * @return ServiceReturn
     */
    public function getNearbyMerchants(GetNearbyMerchantsDTO $dto): ServiceReturn;

    /**
     * Get details of a specific merchant profile.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function getMerchantDetail(string $id): ServiceReturn;

    /**
     * Get full menu of a specific merchant profile.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function getMerchantMenu(string $id): ServiceReturn;
}
