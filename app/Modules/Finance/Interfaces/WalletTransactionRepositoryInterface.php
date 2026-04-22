<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\WalletTransaction;
use Illuminate\Support\Collection;

interface WalletTransactionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get recent transactions for a wallet
     */
    public function getRecentByWalletId(int $walletId, int $limit = 10): Collection;

    /**
     * Paginate transactions by wallet ID (UC-44)
     */
    public function paginateByWalletId(int $walletId, int $page, int $limit): array;

    /**
     * Get total topped up amount (UC-44)
     */
    public function getTotalTopUp(int $walletId): float;

    /**
     * Get total used amount (fees, packages...) (UC-44)
     */
    public function getTotalUsed(int $walletId): float;

    /**
     * Find transaction by ID and wallet ID for security (UC-44 detail)
     */
    public function findByIdAndWallet(int $transactionId, int $walletId): ?WalletTransaction;
}
