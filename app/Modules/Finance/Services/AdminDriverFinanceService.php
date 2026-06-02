<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\AdminDriverFinanceSummaryDTO;
use App\Modules\Finance\Interfaces\AdminDriverFinanceServiceInterface;
use App\Modules\Finance\Interfaces\CommissionRuleRepositoryInterface;
use App\Modules\Finance\Interfaces\DriverSubscriptionRepositoryInterface;
use App\Modules\Finance\Model\CommissionRule;
use App\Modules\Finance\Model\DriverSubscription;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\DriverStatus;
use Illuminate\Support\Facades\DB;

final class AdminDriverFinanceService extends BaseService implements AdminDriverFinanceServiceInterface
{
    public function __construct(
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly RideRepositoryInterface          $rideRepository,
        private readonly DriverSubscriptionRepositoryInterface $driverSubscriptionRepository,
        private readonly CommissionRuleRepositoryInterface     $commissionRuleRepository,
    ) {}

    /**
     * @inheritDoc
     */
    public function getSummary(AdminDriverFinanceSummaryDTO $dto): ServiceReturn
    {
        return $this->execute(function () {
            $totalDrivers    = $this->driverProfileRepository->countTotalDrivers();
            $internalDrivers = $this->driverProfileRepository->countByGroupType(DriverGroupType::INTERNAL);
            $partnerDrivers  = $this->driverProfileRepository->countByGroupType(DriverGroupType::PARTNER);
            $blockedDrivers  = $this->driverProfileRepository->countByStatus(DriverStatus::BANNED);

            $totalRevenue    = $this->rideRepository->sumTotalRevenue();
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

    /**
     * @inheritDoc
     * Báo cáo tài chính chi tiết: GMV theo tháng, hoa hồng theo tháng,
     * phân bổ gói thuê bao và hoa hồng theo loại dịch vụ.
     */
    public function getReports(AdminDriverFinanceSummaryDTO $dto): ServiceReturn
    {
        return $this->execute(function () {
            $year = now()->year;

            // ── 1. GMV & Commission theo tháng (từ bảng rides) ────────────────
            $monthlyRideData = $this->rideRepository->getMonthlyRideDataForFinance($year);

            // Build mảng 12 tháng (fill 0 cho tháng chưa có dữ liệu)
            $gmvMonthly        = [];
            $commissionMonthly = [];
            $monthNames        = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'];

            for ($m = 1; $m <= 12; $m++) {
                $row = $monthlyRideData->get($m);
                $gmvMonthly[] = [
                    'label'      => $monthNames[$m - 1],
                    'value'      => $row ? (float) $row->gmv       : 0,
                    'ride_count' => $row ? (int)   $row->ride_count : 0,
                ];
                $commissionMonthly[] = [
                    'label' => $monthNames[$m - 1],
                    'value' => $row ? (float) $row->commission : 0,
                ];
            }

            // ── 2. Tổng KPI năm ───────────────────────────────────────────────
            $totalGmv        = array_sum(array_column($gmvMonthly, 'value'));
            $totalCommission = array_sum(array_column($commissionMonthly, 'value'));
            $avgRate         = $totalGmv > 0 ? round(($totalCommission / $totalGmv) * 100, 1) : 0;

            // So sánh với năm trước (tháng hiện tại)
            $thisMonthGmv = $monthlyRideData->get(now()->month)?->gmv ?? 0;
            $lastMonthGmv = $monthlyRideData->get(now()->subMonth()->month)?->gmv ?? 0;
            $gmvGrowth = $lastMonthGmv > 0
                ? round((($thisMonthGmv - $lastMonthGmv) / $lastMonthGmv) * 100, 1)
                : 0;

            $thisMonthComm = $monthlyRideData->get(now()->month)?->commission ?? 0;
            $lastMonthComm = $monthlyRideData->get(now()->subMonth()->month)?->commission ?? 0;
            $commGrowth = $lastMonthComm > 0
                ? round((($thisMonthComm - $lastMonthComm) / $lastMonthComm) * 100, 1)
                : 0;

            // ── 3. Gói thuê bao ───────────────────────────────────────────────
            $totalSubs  = $this->driverSubscriptionRepository->countTotalSubscriptionsByYear($year);
            $subsByType = $this->driverSubscriptionRepository->getSubscriptionsGroupedByPackage($year);

            $colors = ['#0049ac', '#f72585', '#00906a', '#b78300'];
            $subPackages = $subsByType->map(function ($row, $i) use ($colors) {
                return [
                    'label' => $row->name,
                    'value' => (int) $row->count,
                    'color' => $colors[$i % count($colors)],
                ];
            })->values()->toArray();

            // Sub growth
            $lastMonthSubs  = $this->driverSubscriptionRepository->countSubscriptionsByMonth($year, now()->subMonth()->month);
            $thisMonthSubs  = $this->driverSubscriptionRepository->countSubscriptionsByMonth($year, now()->month);
            $subGrowth = $lastMonthSubs > 0
                ? round((($thisMonthSubs - $lastMonthSubs) / $lastMonthSubs) * 100, 1)
                : 0;

            // ── 4. Hoa hồng theo loại dịch vụ (từ commission_rules) ──────────
            $activeRules    = $this->commissionRuleRepository->getAllActiveRules();
            $ruleColors     = [1 => '#0049ac', 2 => '#f72585', 3 => '#00906a', 4 => '#b78300'];
            $serviceLabels  = [1 => 'Chuyến xe', 2 => 'Ăn uống', 3 => 'Giao hàng', 4 => 'Khác'];

            // Tính commission theo service_type dựa trên GMV rides (chỉ có ride data thật)
            $commissionByType = [];
            $rideCommission   = (float) $totalCommission;

            foreach ($serviceLabels as $sType => $sLabel) {
                // Lấy tỷ lệ từ active commission rule hoặc mặc định
                $rule = $activeRules->where('service_type', $sType)->first();
                $rate = $rule ? $rule->commission_rate : 0;

                if ($rate > 0) {
                    $commissionByType[] = [
                        'label' => $sLabel,
                        'color' => $ruleColors[$sType] ?? '#64748b',
                        'rate'  => $rate,
                    ];
                }
            }

            // Phân bổ commission thực tế theo tỷ lệ cấu hình
            $totalRate = array_sum(array_column($commissionByType, 'rate'));
            $commissionByType = array_map(function ($item) use ($rideCommission, $totalRate) {
                $pct   = $totalRate > 0 ? round(($item['rate'] / $totalRate) * 100) : 0;
                $value = round($rideCommission * ($pct / 100));
                return array_merge($item, ['value' => $value, 'pct' => $pct]);
            }, $commissionByType);

            // ── 5. Báo cáo theo mốc thời gian (6 tháng gần nhất) ────────────
            $topPeriods = [];
            for ($m = min(now()->month, 12); $m >= max(1, now()->month - 5); $m--) {
                $row = $monthlyRideData->get($m);
                if (!$row) continue;
                $gmv  = (float) $row->gmv;
                $comm = (float) $row->commission;
                $rate = $gmv > 0 ? round(($comm / $gmv) * 100, 1) : 0;

                $topPeriods[] = [
                    'period'     => 'Tháng ' . $m . '/' . $year,
                    'gmv'        => $gmv,
                    'commission' => $comm,
                    'rate'       => $rate,
                    'ride_count' => (int) $row->ride_count,
                ];
            }

            return [
                'summary' => [
                    'total_gmv'           => $totalGmv,
                    'total_commission'    => $totalCommission,
                    'avg_commission_rate' => $avgRate,
                    'total_subscriptions' => $totalSubs,
                    'gmv_growth'          => $gmvGrowth,
                    'commission_growth'   => $commGrowth,
                    'sub_growth'          => $subGrowth,
                ],
                'gmv_monthly'        => $gmvMonthly,
                'commission_monthly' => $commissionMonthly,
                'sub_packages'       => $subPackages,
                'commission_by_type' => $commissionByType,
                'top_periods'        => $topPeriods,
            ];
        });
    }
}

