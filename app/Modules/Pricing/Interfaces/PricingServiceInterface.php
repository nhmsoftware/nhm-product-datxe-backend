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

    /**
     * Get all pricing configurations and global settings.
     * UC-91 Configure Pricing
     *
     * @return ServiceReturn
     */
    public function getConfigs(): ServiceReturn;

    /**
     * Update pricing configuration for a vehicle type.
     * UC-91 Configure Pricing
     *
     * @param \App\Modules\Pricing\DTO\UpdatePricingConfigDTO $dto
     * @return ServiceReturn
     */
    public function updateConfig(\App\Modules\Pricing\DTO\UpdatePricingConfigDTO $dto): ServiceReturn;

    /**
     * Archive / deactivate pricing configuration for a vehicle type.
     */
    public function archiveConfig(int $vehicleTypeId): ServiceReturn;

    /**
     * Toggle system-wide free mode.
     * UC-91 Configure Pricing
     *
     * @param \App\Modules\Pricing\DTO\ToggleFreeModeDTO $dto
     * @return ServiceReturn
     */
    public function toggleFreeMode(\App\Modules\Pricing\DTO\ToggleFreeModeDTO $dto): ServiceReturn;

    /**
     * Get all surge pricing rules.
     * UC-96 Set Surge Pricing
     *
     * @return ServiceReturn
     */
    public function getSurgeRules(): ServiceReturn;

    /**
     * Create or update a surge pricing rule.
     * UC-96 Set Surge Pricing
     *
     * @param \App\Modules\Pricing\DTO\SurgeRuleDTO $dto
     * @param string|null $ruleId
     * @return ServiceReturn
     */
    public function saveSurgeRule(\App\Modules\Pricing\DTO\SurgeRuleDTO $dto, ?string $ruleId = null): ServiceReturn;

    /**
     * Delete a surge pricing rule.
     *
     * @param string $ruleId
     * @return ServiceReturn
     */
    public function deleteSurgeRule(string $ruleId): ServiceReturn;

    /**
     * Reset pricing to default by deleting custom config.
     */
    public function resetToDefault(int $vehicleType): ServiceReturn;

    /**
     * Lấy lịch sử thay đổi cấu hình giá cho một loại xe (UC-125).
     */
    public function getPricingHistory(int $vehicleType): ServiceReturn;
}
