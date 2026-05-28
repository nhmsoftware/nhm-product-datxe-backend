<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ViewSpendingSummaryDTO;
use App\Modules\Finance\Interfaces\SpendingServiceInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Order\Model\Enums\OrderType;

final class SpendingService extends BaseService implements SpendingServiceInterface
{
    public function __construct(
        private readonly RideRepositoryInterface $rideRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSummary(ViewSpendingSummaryDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Lấy dữ liệu từ module Ride
            $rideSummary = $this->rideRepository->getSpendingSummary($dto->customerId, $dto->startDate, $dto->endDate);

            // 2. Mock dữ liệu cho các dịch vụ chưa triển khai (Food, Delivery)
            $foodSummary = ['total_amount' => 0.0, 'total_count' => 0];
            $deliverySummary = ['total_amount' => 0.0, 'total_count' => 0];

            // 3. Tổng hợp dữ liệu
            $totalAmount = (float)($rideSummary['total_amount'] + $foodSummary['total_amount'] + $deliverySummary['total_amount']);
            $totalCount = (int)($rideSummary['total_count'] + $foodSummary['total_count'] + $deliverySummary['total_count']);

            return [
                'range_label'   => $dto->rangeLabel,
                'total_amount'  => $totalAmount,
                'total_count'   => $totalCount,
                'breakdown'     => [
                    [
                        'service' => OrderType::RIDE->value,
                        'amount'  => (float)$rideSummary['total_amount'],
                        'count'   => (int)$rideSummary['total_count'],
                    ],
                    [
                        'service' => OrderType::FOOD->value,
                        'amount'  => (float)$foodSummary['total_amount'],
                        'count'   => (int)$foodSummary['total_count'],
                    ],
                    [
                        'service' => OrderType::DELIVERY->value,
                        'amount'  => (float)$deliverySummary['total_amount'],
                        'count'   => (int)$deliverySummary['total_count'],
                    ],
                ],
            ];
        });
    }
}
