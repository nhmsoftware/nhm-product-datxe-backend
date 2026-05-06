<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\RiskManagement\Interfaces\PenaltyRuleRepositoryInterface;
use App\Modules\RiskManagement\Model\PenaltyRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class PenaltyRuleRepository extends BaseRepository implements PenaltyRuleRepositoryInterface
{
    public function getModel(): string
    {
        return PenaltyRule::class;
    }

    /**
     * @inheritDoc
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (!empty($filters['keyword'])) {
            $query->where('name', 'ilike', '%' . $filters['keyword'] . '%');
        }

        if (!empty($filters['violation_type'])) {
            $query->where('violation_type', $filters['violation_type']);
        }

        if (!empty($filters['applicable_role'])) {
            $query->where('applicable_role', $filters['applicable_role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * @inheritDoc
     */
    public function findActiveRule(int $violationType, int $role): ?PenaltyRule
    {
        /** @var PenaltyRule|null */
        return $this->model->query()
            ->where('violation_type', $violationType)
            ->where('applicable_role', $role)
            ->where('is_active', true)
            ->first();
    }
}
