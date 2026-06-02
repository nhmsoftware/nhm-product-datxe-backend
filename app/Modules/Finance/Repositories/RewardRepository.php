<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\DTO\RewardHistoryDTO;
use App\Modules\Finance\Interfaces\RewardRepositoryInterface;
use App\Modules\Finance\Model\RewardTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

final class RewardRepository extends BaseRepository implements RewardRepositoryInterface
{
    public function getModel(): string
    {
        return RewardTransaction::class;
    }

    /**
     * Lấy lịch sử giao dịch điểm có phân trang và lọc (UC-24)
     */
    public function getTransactionsPaginated(RewardHistoryDTO $dto): LengthAwarePaginator
    {
        $query = $this->getQuery()->where('customer_id', $dto->customerId);

        if ($dto->type !== null) {
            $query->where('type', $dto->type->value);
        }

        if ($dto->startDate !== null) {
            $query->whereDate('created_at', '>=', $dto->startDate);
        }

        if ($dto->endDate !== null) {
            $query->whereDate('created_at', '<=', $dto->endDate);
        }

        return $query->orderByDesc('created_at')->paginate($dto->perPage);
    }

    /**
     * Lấy chi tiết một giao dịch điểm của khách hàng (UC-24-5)
     */
    public function getTransactionDetail(string $transactionId, string $customerId): ?RewardTransaction
    {
        /** @var RewardTransaction|null */
        return $this->getQuery()
            ->where('id', $transactionId)
            ->where('customer_id', $customerId)
            ->first();
    }
}
