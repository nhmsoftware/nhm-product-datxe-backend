<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\AdminDriverFinanceSummaryDTO;
use App\Modules\Finance\Interfaces\AdminDriverFinanceServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\DriverStatus;

final class AdminDriverFinanceService extends BaseService implements AdminDriverFinanceServiceInterface
{
    public function __construct(
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly RideRepositoryInterface          $rideRepository,
    ) {}

    /**
     * @inheritDoc
     */
    public function getSummary(AdminDriverFinanceSummaryDTO $dto): ServiceReturn
    {
        return $this->execute(function () {
            $totalDrivers = $this->driverProfileRepository->countTotalDrivers();
            $internalDrivers = $this->driverProfileRepository->countByGroupType(DriverGroupType::INTERNAL);
            $partnerDrivers = $this->driverProfileRepository->countByGroupType(DriverGroupType::PARTNER);
            $blockedDrivers = $this->driverProfileRepository->countByStatus(DriverStatus::BANNED);

            $totalRevenue = $this->rideRepository->sumTotalRevenue();
            $totalCommission = $this->rideRepository->sumTotalCommission();

            return [
                'total_drivers'          => $totalDrivers,
                'total_drivers_internal' => $internalDrivers,
                'total_drivers_partner'  => $partnerDrivers,
                'total_revenue'          => $totalRevenue,
                'total_commission'       => $totalCommission,
                'total_drivers_blocked'  => $blockedDrivers,
                'currency'               => 'VND',
            ];
        });
    }
}
