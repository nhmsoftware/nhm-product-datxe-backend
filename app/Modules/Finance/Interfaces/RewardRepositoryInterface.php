<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\DTO\RewardHistoryDTO;
use App\Modules\Finance\Model\RewardTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface RewardRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy lịch sử giao dịch điểm có phân trang và lọc (UC-24)
     *
     * @param RewardHistoryDTO $dto
     * @return LengthAwarePaginator
     */
    public function getTransactionsPaginated(RewardHistoryDTO $dto): LengthAwarePaginator;

    /**
     * Lấy chi tiết một giao dịch điểm của khách hàng (UC-24-5)
     *
     * @param string $transactionId
     * @param string $customerId
     * @return RewardTransaction|null
     */
    public function getTransactionDetail(string $transactionId, string $customerId): ?RewardTransaction;
}
