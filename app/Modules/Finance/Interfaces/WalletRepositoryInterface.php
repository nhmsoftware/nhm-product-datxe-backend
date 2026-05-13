<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\Wallet;

interface WalletRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find wallet by user ID
     */
    public function findByUserId(string $userId): ?Wallet;

    /**
     * Create wallet for user if not exists
     */
    public function firstOrCreateForUser(string $userId): Wallet;

    /**
     * Lấy danh sách user_id có số dư ví thấp hơn mức tối thiểu.
     */
    public function getLowBalanceUserIds(float $minBalance): array;
}
