<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Dashboard\Interfaces\DashboardServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\User\Model\Enums\UserRole;

final class DashboardService extends BaseService implements DashboardServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly RideRepositoryInterface $rideRepository,
    ) {}

    public function getDashboardStats(): ServiceReturn
    {
        return $this->execute(function (): array {
            $totalUsers = $this->userRepository->countUsersByRoles([
                UserRole::Customer->value,
                UserRole::Driver->value,
                UserRole::Merchants->value,
            ]);

            $totalOrders = $this->rideRepository->countTotalOrders();
            $totalRevenue = $this->rideRepository->sumTotalRevenue();
            $activeMerchants = $this->userRepository->countActiveMerchants();
            $activeDrivers = $this->driverProfileRepository->countActiveDrivers();

            return [
                'total_users'      => $totalUsers,
                'total_orders'     => $totalOrders,
                'total_revenue'    => (float) $totalRevenue,
                'active_merchants' => $activeMerchants,
                'active_drivers'   => $activeDrivers,
            ];
        }, useTransaction: false);
    }

    public function getRevenueReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            return $this->rideRepository->getRevenueAnalytics(
                $dto->startDate,
                $dto->endDate,
                $dto->interval
            );
        });
    }

    public function getAreaReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            return $this->rideRepository->getAreaAnalytics(
                $dto->startDate,
                $dto->endDate
            );
        });
    }

    public function getCommissionReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $summary = $this->rideRepository->getCommissionAnalytics(
                $dto->startDate,
                $dto->endDate
            );

            $details = $this->rideRepository->getCommissionDetails(
                $dto->startDate,
                $dto->endDate
            );

            return [
                'summary' => $summary,
                'details' => $details,
            ];
        });
    }

    public function getOrderReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            return $this->rideRepository->getOrderOperationalStats(
                $dto->startDate,
                $dto->endDate
            );
        });
    }

    public function getDetailedReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            return [
                'vehicle_types' => $this->rideRepository->getVehicleTypeAnalytics($dto->startDate, $dto->endDate),
                'ride_types'    => $this->rideRepository->getRideTypeAnalytics($dto->startDate, $dto->endDate),
            ];
        });
    }

    public function getTopDriversReport(\App\Modules\Dashboard\DTO\DashboardReportFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            return $this->rideRepository->getTopDriversAnalytics(
                $dto->startDate,
                $dto->endDate,
                10,
                $dto->driverGroupType
            );
        });
    }
}
