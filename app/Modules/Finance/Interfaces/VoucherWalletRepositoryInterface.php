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
     * @param string $customerId
     * @param string $voucherId
     * @return bool
     */
    public function isSavedByCustomer(string $customerId, string $voucherId): bool;

    /**
     * Lưu voucher vào ví của khách hàng.
     * 
     * @param string $customerId
     * @param string $voucherId
     * @return bool
     */
    public function saveToWallet(string $customerId, string $voucherId): bool;

    /**
     * Lấy danh sách voucher trong ví của khách hàng.
     * 
     * @param string $customerId
     * @return \Illuminate\Support\Collection
     */
    public function findVouchersByCustomer(string $customerId): \Illuminate\Support\Collection;

    /**
     * Đánh dấu voucher đã sử dụng.
     * 
     * @param string $customerId
     * @param string $voucherId
     * @return bool
     */
    public function markAsUsed(string $customerId, string $voucherId): bool;
}
