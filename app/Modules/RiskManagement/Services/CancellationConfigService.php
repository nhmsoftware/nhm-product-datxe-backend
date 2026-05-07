<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\RiskManagement\DTO\CreateCancellationConfigDTO;
use App\Modules\RiskManagement\DTO\UpdateCancellationConfigDTO;
use App\Modules\RiskManagement\Interfaces\CancellationConfigRepositoryInterface;
use App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface;

final class CancellationConfigService extends BaseService implements CancellationConfigServiceInterface
{
    public function __construct(
        private readonly CancellationConfigRepositoryInterface $repository
    ) {}

    public function listConfigs(array $filters): ServiceReturn
    {
        return $this->execute(function () use ($filters) {
            return $this->repository->search($filters);
        });
    }

    public function createConfig(CreateCancellationConfigDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            return $this->repository->create($dto->toArray());
        });
    }

    public function updateConfig(string $id, UpdateCancellationConfigDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($id, $dto) {
            $config = $this->repository->findById($id);
            $this->validate($config !== null, 'Không tìm thấy cấu hình.', 404);

            return $this->repository->update($id, $dto->toArray());
        });
    }

    public function getConfig(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $config = $this->repository->findById($id);
            $this->validate($config !== null, 'Không tìm thấy cấu hình.', 404);

            return $config;
        });
    }

    public function deleteConfig(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $config = $this->repository->findById($id);
            $this->validate($config !== null, 'Không tìm thấy cấu hình.', 404);

            return $this->repository->delete($id);
        });
    }

    public function getApplicableFee(int $rideType, int $minutesUntilPickup): ServiceReturn
    {
        return $this->execute(function () use ($rideType, $minutesUntilPickup) {
            $rule = $this->repository->findApplicableRule($rideType, $minutesUntilPickup);

            if (!$rule) {
                return [
                    'fee_type' => null,
                    'fee_value' => 0,
                ];
            }

            return [
                'fee_type' => $rule->fee_type->value,
                'fee_value' => (float) $rule->fee_value,
            ];
        });
    }
}
