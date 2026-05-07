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
     * @param string $customerId
     * @param string|null $serviceType Loại dịch vụ để lọc (ride/food)
     * @return ServiceReturn
     */
    public function listVouchers(string $customerId, ?string $serviceType = null): ServiceReturn;

    /**
     * Xem chi tiết một voucher.
     *
     * @param string $customerId
     * @param string $voucherId
     * @return ServiceReturn
     */
    public function getVoucherDetail(string $customerId, string $voucherId): ServiceReturn;

    /**
     * Lưu voucher vào ví cá nhân.
     *
     * @param string $customerId
     * @param string $voucherId
     * @return ServiceReturn
     */
    public function saveVoucher(string $customerId, string $voucherId): ServiceReturn;

    /**
     * Áp dụng voucher nhanh từ danh sách voucher (UC-22).
     *
     * @param ApplyVoucherQuickDTO $dto
     * @return ServiceReturn
     */
    public function applyVoucherQuick(ApplyVoucherQuickDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách voucher đã lưu trong ví của khách hàng.
     * 
     * @param string $customerId
     * @return ServiceReturn
     */
    public function getSavedVouchers(string $customerId): ServiceReturn;

    /**
     * Kiểm tra tính hợp lệ và tính toán mức giảm giá của voucher.
     * 
     * @param string $customerId
     * @param string $voucherCode
     * @param float $orderAmount
     * @param string $serviceType 'ride'|'food'
     * @return ServiceReturn
     */
    public function validateAndCalculateDiscount(string $customerId, string $voucherCode, float $orderAmount, string $serviceType): ServiceReturn;

    /**
     * Đánh dấu voucher đã được sử dụng sau khi đơn hàng hoàn tất.
     * 
     * @param string $customerId
     * @param string $voucherCode
     * @return ServiceReturn
     */
    public function markAsUsed(string $customerId, string $voucherCode): ServiceReturn;
}
