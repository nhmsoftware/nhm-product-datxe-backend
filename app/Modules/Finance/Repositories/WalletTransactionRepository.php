<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\WalletTransactionRepositoryInterface;
use App\Modules\Finance\Model\Enums\WalletTransactionType;
use App\Modules\Finance\Model\WalletTransaction;
use Illuminate\Support\Collection;

final class WalletTransactionRepository extends BaseRepository implements WalletTransactionRepositoryInterface
{
    public function getModel(): string
    {
        return WalletTransaction::class;
    }

    public function getRecentByWalletId(string $walletId, int $limit = 10): Collection
    {
        return $this->getQuery()
            ->where('wallet_id', $walletId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function paginateByWalletId(string $walletId, int $page, int $limit): array
    {
        $paginator = $this->getQuery()
            ->where('wallet_id', $walletId)
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

    public function getTotalTopUp(string $walletId): float
    {
        return (float) $this->getQuery()
            ->where('wallet_id', $walletId)
            ->where('type', WalletTransactionType::TOP_UP->value)
            ->sum('amount');
    }

    public function getTotalUsed(string $walletId): float
    {
        // Fee and Withdrawal are "used"
        return (float) $this->getQuery()
            ->where('wallet_id', $walletId)
            ->whereIn('type', [
                WalletTransactionType::FEE->value,
                WalletTransactionType::WITHDRAWAL->value
            ])
            ->sum('amount');
    }

    public function findByIdAndWallet(string $transactionId, string $walletId): ?WalletTransaction
    {
        /** @var WalletTransaction|null */
        return $this->getQuery()
            ->where('id', $transactionId)
            ->where('wallet_id', $walletId)
            ->first();
    }
}
