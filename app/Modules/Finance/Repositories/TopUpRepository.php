<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\TopUpRepositoryInterface;
use App\Modules\Finance\Model\Enums\TopUpStatus;
use App\Modules\Finance\Model\TopUp;

final class TopUpRepository extends BaseRepository implements TopUpRepositoryInterface
{
    public function getModel(): string
    {
        return TopUp::class;
    }

    public function findByExternalId(string $externalId): ?TopUp
    {
        /** @var TopUp|null */
        return $this->getQuery()->where('external_id', $externalId)->first();
    }

    /**
     * Tìm top-up theo ID và user_id (đảm bảo ownership).
     */
    public function findByIdAndUser(string $id, string $userId): ?TopUp
    {
        /** @var TopUp|null */
        return $this->getQuery()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Lấy lịch sử nạp tiền của user có phân trang.
     */
    public function getPaginatedByUser(string $userId, int $page, int $limit): array
    {
        $paginator = $this->getQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }

    /**
     * Kiểm tra xem có giao dịch pending nào đối với phương thức thanh toán này không.
     */
    public function hasPendingTopUps(string $paymentMethodCode): bool
    {
        return $this->getQuery()
            ->where('payment_method', $paymentMethodCode)
            ->where('status', TopUpStatus::PENDING->value)
            ->exists();
    }
}

