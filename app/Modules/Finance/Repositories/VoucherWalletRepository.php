<?php

declare(strict_types=1);

namespace App\Modules\Finance\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Finance\Interfaces\VoucherWalletRepositoryInterface;
use App\Modules\Finance\Model\VoucherWallet;

final class VoucherWalletRepository extends BaseRepository implements VoucherWalletRepositoryInterface
{
    public function getModel(): string
    {
        return VoucherWallet::class;
    }

    /**
     * @inheritDoc
     */
    public function isSavedByCustomer(string $customerId, string $voucherId): bool
    {
        return $this->getQuery()
            ->where('customer_id', $customerId)
            ->where('voucher_id', $voucherId)
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function saveToWallet(string $customerId, string $voucherId): bool
    {
        $wallet = $this->getQuery()->withTrashed()
            ->where('customer_id', $customerId)
            ->where('voucher_id', $voucherId)
            ->first();

        if ($wallet) {
            $wallet->restore();
            $wallet->saved_at = now();
            return $wallet->save();
        }

        return (bool) $this->getQuery()->create([
            'customer_id' => $customerId,
            'voucher_id' => $voucherId,
            'saved_at' => now(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function findVouchersByCustomer(string $customerId): \Illuminate\Support\Collection
    {
        return $this->getQuery()
            ->with('voucher')
            ->where('customer_id', $customerId)
            ->whereNull('used_at')
            ->get()
            ->pluck('voucher')
            ->filter();
    }

    /**
     * @inheritDoc
     */
    public function markAsUsed(string $customerId, string $voucherId): bool
    {
        return (bool) $this->getQuery()
            ->where('customer_id', $customerId)
            ->where('voucher_id', $voucherId)
            ->update(['used_at' => now()]);
    }
}
