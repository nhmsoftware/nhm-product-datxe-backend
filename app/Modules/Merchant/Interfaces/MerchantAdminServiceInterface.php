<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\CreateMerchantDTO;
use App\Modules\Merchant\DTO\MerchantFilterDTO;
use App\Modules\Merchant\DTO\UpdateMerchantDTO;

interface MerchantAdminServiceInterface
{
    /**
     * Get list of merchants with filters.
     * UC-86 Manage Merchant
     */
    public function getMerchants(MerchantFilterDTO $dto): ServiceReturn;

    /**
     * Get merchant details.
     * UC-86 Manage Merchant
     */
    public function getMerchantDetails(string $id): ServiceReturn;

    /**
     * Create merchant account from admin portal.
     */
    public function createMerchant(CreateMerchantDTO $dto): ServiceReturn;

    /**
     * Update merchant from admin portal.
     */
    public function updateMerchant(UpdateMerchantDTO $dto): ServiceReturn;

    /**
     * Soft delete merchant from admin portal.
     */
    public function deleteMerchant(string $id): ServiceReturn;

    /**
     * Approve merchant registration.
     * UC-86 Manage Merchant
     */
    public function approveMerchant(string $id): ServiceReturn;

    /**
     * Reject merchant registration.
     * UC-86 Manage Merchant
     */
    public function rejectMerchant(string $id, string $reason): ServiceReturn;

    /**
     * Lock/Unlock merchant account.
     * UC-89 Lock/UnLock Merchant
     */
    public function toggleLockMerchant(string $id, bool $lock, ?string $reason = null, ?int $lockedDays = null): ServiceReturn;
}
