<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ConfigureCommissionDTO;
use App\Modules\Finance\Events\CommissionRuleUpdated;
use App\Modules\Finance\Interfaces\CommissionRuleRepositoryInterface;
use App\Modules\Finance\Interfaces\CommissionRuleServiceInterface;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use Illuminate\Support\Facades\Auth;

final class CommissionRuleService extends BaseService implements CommissionRuleServiceInterface
{
    public function __construct(
        private readonly CommissionRuleRepositoryInterface $commissionRuleRepository
    ) {}

    public function configure(ConfigureCommissionDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // 1. Kiểm tra trùng lặp (Overlap)
            $isOverlap = $this->commissionRuleRepository->hasOverlappingRule(
                $dto->serviceType,
                $dto->scope->value,
                $dto->areaId,
                $dto->effectiveFrom->toDateTimeString(),
                $dto->effectiveTo?->toDateTimeString()
            );

            $this->validate(!$isOverlap, 'Đã tồn tại cấu hình hoa hồng đang hoạt động trong khoảng thời gian này.', 400);

            // 2. Tạo rule mới
            $rule = $this->commissionRuleRepository->create([
                'name'            => $dto->name,
                'service_type'    => $dto->serviceType->value,
                'scope'           => $dto->scope->value,
                'area_id'         => $dto->areaId,
                'commission_rate' => $dto->commissionRate,
                'min_commission'  => $dto->minCommission,
                'max_commission'  => $dto->maxCommission,
                'is_active'       => $dto->isActive,
                'effective_from'  => $dto->effectiveFrom,
                'effective_to'    => $dto->effectiveTo,
            ]);

            // 3. Phát event
            event(new CommissionRuleUpdated(
                ruleId:      $rule->id,
                serviceType: $rule->service_type->value,
                oldRate:     0, // Giả sử đây là rule mới, chưa quan tâm rule cũ ở đây
                newRate:     $rule->commission_rate,
                adminId:     Auth::id() // Lấy từ auth nếu có
            ));

            return $rule->toArray();
        }, useTransaction: true);
    }

    public function listConfigs(): ServiceReturn
    {
        return $this->execute(function () {
            return $this->commissionRuleRepository->getAllRules()->toArray();
        });
    }

    public function getApplicableCommission(CommissionServiceType $serviceType, ?string $areaId = null): ServiceReturn
    {
        return $this->execute(function () use ($serviceType, $areaId) {
            $rule = $this->commissionRuleRepository->getActiveRule($serviceType, $areaId);
            $this->validate($rule !== null, 'Không tìm thấy cấu hình hoa hồng hợp lệ.', 404);

            return $rule->toArray();
        });
    }

    public function deleteRule(string $ruleId): ServiceReturn
    {
        return $this->execute(function () use ($ruleId) {
            $rule = $this->commissionRuleRepository->find($ruleId);
            $this->validate($rule !== null, 'Không tìm thấy cấu hình.', 404);

            // Thay vì xóa cứng, ta deactivate hoặc soft delete
            $this->commissionRuleRepository->updateById($ruleId, ['is_active' => false]);
            $this->commissionRuleRepository->deleteById($ruleId);

            return ['id' => $ruleId];
        }, useTransaction: true);
    }
}
