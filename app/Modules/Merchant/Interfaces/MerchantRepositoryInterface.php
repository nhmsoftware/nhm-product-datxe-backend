<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\MerchantProfile;
use Illuminate\Pagination\LengthAwarePaginator;

interface MerchantRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a merchant profile by User ID.
     *
     * @param string $userId
     * @return MerchantProfile|null
     */
    public function findByUserId(string $userId): ?MerchantProfile;

    /**
     * Check if store name already exists.
     *
     * @param string $storeName
     * @param string|null $excludeUserId
     * @return bool
     */
    public function isStoreNameExists(string $storeName, ?string $excludeUserId = null): bool;

    /**
     * Update merchant opening hours.
     *
     * @param string $merchantProfileId
     * @param array $schedule
     * @return bool
     */
    public function updateOpeningHoursSchedule(string $merchantProfileId, array $schedule): bool;

    /**
     * Search and paginate merchants.
     *
     * @param \App\Modules\Merchant\DTO\MerchantFilterDTO $dto
     * @return LengthAwarePaginator
     */
    public function searchMerchants(\App\Modules\Merchant\DTO\MerchantFilterDTO $dto): LengthAwarePaginator;

    /**
     * Get nearby merchants based on customer location.
     *
     * @param \App\Modules\Merchant\DTO\GetNearbyMerchantsDTO $dto
     * @return LengthAwarePaginator
     */
    public function getNearbyMerchants(\App\Modules\Merchant\DTO\GetNearbyMerchantsDTO $dto): LengthAwarePaginator;

    /**
     * Get merchant details for customer app.
     *
     * @param string $id
     * @return MerchantProfile|null
     */
    public function getByIdForCustomer(string $id): ?MerchantProfile;

    /**
     * Update average rating and total orders for a merchant.
     *
     * @param string $merchantProfileId
     * @param float $averageRating
     * @param int $totalOrders
     * @return bool
     */
    public function updateRatingStats(string $merchantProfileId, float $averageRating, int $totalOrders): bool;
}
