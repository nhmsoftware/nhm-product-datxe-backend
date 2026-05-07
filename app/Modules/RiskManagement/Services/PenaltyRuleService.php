<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\RiskManagement\DTO\CreatePenaltyRuleDTO;
use App\Modules\RiskManagement\DTO\UpdatePenaltyRuleDTO;
use App\Modules\RiskManagement\Events\PenaltyRuleCreated;
use App\Modules\RiskManagement\Interfaces\PenaltyRuleRepositoryInterface;
use App\Modules\RiskManagement\Interfaces\PenaltyRuleServiceInterface;
use App\Modules\RiskManagement\Model\PenaltyRule;

final class PenaltyRuleService extends BaseService implements PenaltyRuleServiceInterface
{
    public function __construct(
        private readonly PenaltyRuleRepositoryInterface $penaltyRuleRepository
    ) {}

    /**
     * @inheritDoc
     */
    public function listRules(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters) {
            return $this->penaltyRuleRepository->search($filters);
        });
    }

    /**
     * @inheritDoc
     */
    public function createRule(CreatePenaltyRuleDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // Check if rule already exists for this violation type and role
            $existing = $this->penaltyRuleRepository->findActiveRule($dto->violationType, $dto->applicableRole);
            $this->validate($existing === null, 'Quy tắc xử phạt này đã tồn tại cho đối tượng và loại vi phạm này.', 400);

            /** @var PenaltyRule $rule */
            $rule = $this->penaltyRuleRepository->create($dto->toArray());

            event(new PenaltyRuleCreated(
                ruleId: (string) $rule->id,
                ruleName: $rule->name,
                violationType: $rule->violation_type->value,
                applicableRole: $rule->applicable_role->value
            ));

            return $rule;
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function updateRule(string $id, UpdatePenaltyRuleDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($id, $dto) {
            /** @var PenaltyRule|null $rule */
            $rule = $this->penaltyRuleRepository->findById($id);
            $this->validate($rule !== null, 'Không tìm thấy quy tắc xử phạt.', 404);

            $updated = $this->penaltyRuleRepository->updateById($id, $dto->toArray());
            $this->validate($updated !== false, 'Cập nhật quy tắc thất bại.');

            return $this->penaltyRuleRepository->findById($id);
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function getRule(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $rule = $this->penaltyRuleRepository->findById($id);
            $this->validate($rule !== null, 'Không tìm thấy quy tắc xử phạt.', 404);

            return $rule;
        });
    }

    /**
     * @inheritDoc
     */
    public function toggleStatus(string $id, bool $isActive): ServiceReturn
    {
        return $this->execute(function () use ($id, $isActive) {
            $updated = $this->penaltyRuleRepository->updateById($id, ['is_active' => $isActive]);
            $this->validate($updated !== false, 'Cập nhật trạng thái thất bại.');

            return true;
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteRule(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $success = $this->penaltyRuleRepository->deleteById($id);
            $this->validate($success, 'Xóa quy tắc thất bại.');

            return true;
        });
    }
}
