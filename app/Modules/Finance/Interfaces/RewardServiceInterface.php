<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\RewardHistoryDTO;

interface RewardServiceInterface
{
    /**
     * Lấy tổng quan điểm thưởng (số dư, tổng nhận, tổng tiêu) (UC-24)
     */
    public function getRewardOverview(int $customerId): ServiceReturn;

    /**
     * Lấy danh sách lịch sử giao dịch điểm (UC-24)
     */
    public function getHistory(RewardHistoryDTO $dto): ServiceReturn;

    /**
     * Lấy chi tiết một giao dịch điểm (UC-24-5)
     */
    public function getTransactionDetail(int $customerId, int $transactionId): ServiceReturn;
}
