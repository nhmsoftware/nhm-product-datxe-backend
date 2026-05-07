<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\MerchantProfile;

interface MerchantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find merchant profile by user ID.
     * UC-52 Register Merchant
     */
    public function findByUserId(string $userId): ?MerchantProfile;

    /**
     * Check if CCCD (Citizen ID) is already used.
     * UC-52 Register Merchant
     */
    public function isCitizenIdExists(string $citizenId, ?string $excludeUserId = null): bool;

    /**
     * Check if Store Name is already exists.
     * UC-52 Register Merchant
     */
    public function isStoreNameExists(string $storeName, ?string $excludeUserId = null): bool;

    /**
     * Update weekly opening hours schedule.
     * UC-54 Set Opening Hours
     */
    public function updateOpeningHoursSchedule(string $merchantProfileId, array $schedule): bool;
    /**
     * Search and paginate merchants for Admin.
     * UC-86 Manage Merchant
     */
    public function searchMerchants(\App\Modules\Merchant\DTO\MerchantFilterDTO $dto): \Illuminate\Pagination\LengthAwarePaginator;
}
