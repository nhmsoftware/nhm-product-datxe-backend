<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\WalletRepositoryInterface;
use App\Modules\Finance\Model\Wallet;

final class WalletRepository extends BaseRepository implements WalletRepositoryInterface
{
    public function getModel(): string
    {
        return Wallet::class;
    }

    public function findByUserId(string $userId): ?Wallet
    {
        /** @var Wallet|null */
        return $this->model->where('user_id', $userId)->first();
    }

    public function firstOrCreateForUser(string $userId): Wallet
    {
        /** @var Wallet */
        return $this->model->firstOrCreate(['user_id' => $userId], [
            'balance'         => 0,
            'total_earned'    => 0,
            'total_withdrawn' => 0,
        ]);
    }
}
