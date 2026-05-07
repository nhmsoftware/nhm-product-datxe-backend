<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PenaltyRuleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search penalty rules with filters.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator;

    /**
     * Find an active rule by violation type and role.
     *
     * @param int $violationType
     * @param int $role
     * @return \App\Modules\RiskManagement\Model\PenaltyRule|null
     */
    public function findActiveRule(int $violationType, int $role): ?\App\Modules\RiskManagement\Model\PenaltyRule;
}
