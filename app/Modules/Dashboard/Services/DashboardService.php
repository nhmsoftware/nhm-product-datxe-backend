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
                'total_users' => $totalUsers,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'active_merchants' => $activeMerchants,
                'active_drivers' => $activeDrivers,
            ];
        }, useTransaction: false);
    }
}
