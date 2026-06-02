<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\RewardWalletRepositoryInterface;
use App\Modules\Finance\Model\RewardWallet;

final class RewardWalletRepository extends BaseRepository implements RewardWalletRepositoryInterface
{
    public function getModel(): string
    {
        return RewardWallet::class;
    }

    public function findByCustomerId(string $customerId): ?RewardWallet
    {
        /** @var RewardWallet|null */
        return $this->getQuery()->where('customer_id', $customerId)->first();
    }

    public function firstOrCreateWallet(string $customerId): RewardWallet
    {
        /** @var RewardWallet */
        return $this->getQuery()->firstOrCreate(
            ['customer_id' => $customerId],
            ['balance' => 0, 'total_earned' => 0, 'total_used' => 0]
        );
    }
}
