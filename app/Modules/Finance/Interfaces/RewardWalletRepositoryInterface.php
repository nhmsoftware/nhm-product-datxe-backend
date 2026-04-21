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
     * @param string $customerId
     * @return RewardWallet|null
     */
    public function findByCustomerId(string $customerId): ?RewardWallet;

    /**
     * Tạo ví điểm thưởng cho khách hàng nếu chưa có.
     * 
     * @param string $customerId
     * @return RewardWallet
     */
    public function firstOrCreateWallet(string $customerId): RewardWallet;
}
