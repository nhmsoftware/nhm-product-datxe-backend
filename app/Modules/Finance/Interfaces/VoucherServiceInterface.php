<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ApplyVoucherQuickDTO;

/**
 * Interface cho VoucherService.
 */
interface VoucherServiceInterface
{
    /**
     * Lấy danh sách voucher cho khách hàng.
     *
     * @param int $customerId
     * @param string|null $serviceType Loại dịch vụ để lọc (ride/food)
     * @return ServiceReturn
     */
    public function listVouchers(int $customerId, ?string $serviceType = null): ServiceReturn;

    /**
     * Xem chi tiết một voucher.
     *
     * @param int $customerId
     * @param int $voucherId
     * @return ServiceReturn
     */
    public function getVoucherDetail(int $customerId, int $voucherId): ServiceReturn;

    /**
     * Lưu voucher vào ví cá nhân.
     *
     * @param int $customerId
     * @param int $voucherId
     * @return ServiceReturn
     */
    public function saveVoucher(int $customerId, int $voucherId): ServiceReturn;

    /**
     * Áp dụng voucher nhanh từ danh sách voucher (UC-22).
     *
     * @param ApplyVoucherQuickDTO $dto
     * @return ServiceReturn
     */
    public function applyVoucherQuick(ApplyVoucherQuickDTO $dto): ServiceReturn;
}
