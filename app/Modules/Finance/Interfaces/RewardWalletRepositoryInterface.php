<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\RewardWallet;

interface RewardWalletRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy ví điểm thưởng của khách hàng (UC-24)
     * 
     * @param int $customerId
     * @return RewardWallet|null
     */
    public function findByCustomerId(int $customerId): ?RewardWallet;

    /**
     * Tạo ví điểm thưởng cho khách hàng nếu chưa có.
     * 
     * @param int $customerId
     * @return RewardWallet
     */
    public function firstOrCreateWallet(int $customerId): RewardWallet;
}
