<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\RiskManagement\DTO\CreatePenaltyRuleDTO;
use App\Modules\RiskManagement\DTO\UpdatePenaltyRuleDTO;

interface PenaltyRuleServiceInterface
{
    /**
     * List all penalty rules.
     *
     * @param array $filters
     * @return ServiceReturn
     */
    public function listRules(array $filters): ServiceReturn;

    /**
     * Create a new penalty rule.
     *
     * @param CreatePenaltyRuleDTO $dto
     * @return ServiceReturn
     */
    public function createRule(CreatePenaltyRuleDTO $dto): ServiceReturn;

    /**
     * Update an existing penalty rule.
     *
     * @param string $id
     * @param UpdatePenaltyRuleDTO $dto
     * @return ServiceReturn
     */
    public function updateRule(string $id, UpdatePenaltyRuleDTO $dto): ServiceReturn;

    /**
     * Get penalty rule details.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function getRule(string $id): ServiceReturn;

    /**
     * Toggle rule active status.
     *
     * @param string $id
     * @param bool $isActive
     * @return ServiceReturn
     */
    public function toggleStatus(string $id, bool $isActive): ServiceReturn;

    /**
     * Delete a penalty rule.
     *
     * @param string $id
     * @return ServiceReturn
     */
    public function deleteRule(string $id): ServiceReturn;
}
