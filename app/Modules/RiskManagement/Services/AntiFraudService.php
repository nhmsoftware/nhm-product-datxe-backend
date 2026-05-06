<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\RiskManagement\DTO\AdminFraudAlertDTO;
use App\Modules\RiskManagement\DTO\ListFraudAlertsDTO;
use App\Modules\RiskManagement\Interfaces\AntiFraudServiceInterface;
use App\Modules\RiskManagement\Interfaces\FraudAlertRepositoryInterface;

/**
 * Service xử lý logic nghiệp vụ chống gian lận.
 */
final class AntiFraudService extends BaseService implements AntiFraudServiceInterface
{
    public function __construct(
        private readonly FraudAlertRepositoryInterface $fraudAlertRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getOverview(): ServiceReturn
    {
        return $this->execute(function () {
            return $this->fraudAlertRepository->getFraudStatistics();
        });
    }

    /**
     * @inheritDoc
     */
    public function listAlerts(ListFraudAlertsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginator = $this->fraudAlertRepository->listAlerts($dto->toArray(), $dto->perPage);

            $paginator->getCollection()->transform(function ($alert) {
                return AdminFraudAlertDTO::fromModel($alert)->toArray();
            });

            return $paginator;
        });
    }

    /**
     * @inheritDoc
     */
    public function getDetail(string|int $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $alert = $this->fraudAlertRepository->find((string) $id);
            $this->validate($alert !== null, 'Không tìm thấy cảnh báo gian lận.', 404);

            return AdminFraudAlertDTO::fromModel($alert)->toArray();
        });
    }
}
