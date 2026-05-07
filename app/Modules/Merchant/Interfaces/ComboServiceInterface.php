<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\ComboDTO;

interface ComboServiceInterface
{
    /**
     * Get all combos of a merchant
     * UC-61
     */
    public function getMerchantCombos(string $merchantProfileId): ServiceReturn;

    /**
     * Get detail of a combo
     * UC-62
     */
    public function getComboDetail(string $comboId, string $merchantProfileId): ServiceReturn;

    /**
     * Create a new combo
     * UC-54
     */
    public function createCombo(ComboDTO $dto): ServiceReturn;

    /**
     * Update an existing combo
     * UC-55
     */
    public function updateCombo(string $comboId, ComboDTO $dto): ServiceReturn;

    /**
     * Delete a combo
     * UC-56
     */
    public function deleteCombo(string $comboId, string $merchantProfileId): ServiceReturn;

    /**
     * Update combo availability status
     * UC-61
     */
    public function updateStatus(string $comboId, string $merchantProfileId, bool $isAvailable): ServiceReturn;
}
