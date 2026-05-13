<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\UpdateCreditWalletConfigDTO;
use App\Modules\Finance\Events\CreditWalletConfigUpdated;
use App\Modules\Finance\Interfaces\CreditWalletConfigRepositoryInterface;
use App\Modules\Finance\Interfaces\CreditWalletConfigServiceInterface;

final class CreditWalletConfigService extends BaseService implements CreditWalletConfigServiceInterface
{
    public function __construct(
        private readonly CreditWalletConfigRepositoryInterface $configRepository
    ) {}

    public function getConfig(): ServiceReturn
    {
        return $this->execute(function () {
            return $this->configRepository->getLatestConfig();
        });
    }

    public function updateConfig(UpdateCreditWalletConfigDTO $dto, int $adminId): ServiceReturn
    {
        // Validation logic (A2)
        if ($dto->minBalance <= 0) {
            return ServiceReturn::error('Minimum Credit Balance không hợp lệ.');
        }

        return $this->execute(function () use ($dto, $adminId) {
            $data = [
                'min_balance'     => $dto->minBalance,
                'auto_lock'       => $dto->autoLock,
                'commission_rule' => $dto->commissionRule,
                'updated_by'      => $adminId,
            ];

            $updated = $this->configRepository->updateConfig($data);

            if (!$updated) {
                throw new \Exception('Không thể cập nhật Credit Wallet Configuration.');
            }

            // Dispatch Event
            event(new CreditWalletConfigUpdated($data, $adminId));

            return $this->configRepository->getLatestConfig();
        });
    }
}
