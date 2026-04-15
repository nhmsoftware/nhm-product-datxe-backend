<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;

/**
 * Interface cho VoucherWalletRepository.
 */
interface VoucherWalletRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Kiểm tra xem khách hàng đã lưu voucher này chưa.
     * 
     * @param int $customerId
     * @param int $voucherId
     * @return bool
     */
    public function isSavedByCustomer(int $customerId, int $voucherId): bool;

    /**
     * Lưu voucher vào ví của khách hàng.
     * 
     * @param int $customerId
     * @param int $voucherId
     * @return bool
     */
    public function saveToWallet(int $customerId, int $voucherId): bool;
}
