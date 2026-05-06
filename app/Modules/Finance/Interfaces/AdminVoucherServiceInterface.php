<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\AdminListVoucherRequest; // Actually should use DTO but following the pattern
use App\Modules\Finance\DTO\CreateVoucherDTO;
use App\Modules\Finance\DTO\UpdateVoucherDTO;
use App\Modules\Finance\DTO\AssignVoucherDTO;

interface AdminVoucherServiceInterface
{
    /**
     * Lấy danh sách voucher cho Admin (phân trang + lọc).
     * 
     * @param array $filters
     * @return ServiceReturn
     */
    public function listVouchers(array $filters): ServiceReturn;

    /**
     * Lấy chi tiết voucher cho Admin.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function getVoucherDetail(string $id): ServiceReturn;

    /**
     * Tạo voucher mới.
     * 
     * @param CreateVoucherDTO $dto
     * @return ServiceReturn
     */
    public function createVoucher(CreateVoucherDTO $dto): ServiceReturn;

    /**
     * Cập nhật voucher.
     * 
     * @param string $id
     * @param UpdateVoucherDTO $dto
     * @return ServiceReturn
     */
    public function updateVoucher(string $id, UpdateVoucherDTO $dto): ServiceReturn;

    /**
     * Xóa voucher.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function deleteVoucher(string $id): ServiceReturn;

    /**
     * Gán voucher cho người dùng.
     * 
     * @param AssignVoucherDTO $dto
     * @return ServiceReturn
     */
    public function assignVoucher(AssignVoucherDTO $dto): ServiceReturn;

    /**
     * Vô hiệu hóa voucher.
     * 
     * @param string $id
     * @return ServiceReturn
     */
    public function deactivate(string $id): ServiceReturn;
}
