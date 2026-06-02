<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\PaymentMethod;
use Illuminate\Support\Collection;

interface PaymentMethodRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy tất cả phương thức thanh toán đang Active, theo sort_order.
     * UC-45: Driver chỉ thấy phương thức Admin bật Active.
     */
    public function getActiveMethods(): Collection;

    /**
     * Tìm phương thức thanh toán Active theo code.
     * UC-45: Validate phương thức Driver chọn.
     *
     * @param string $code Ví dụ: 'momo', 'bank_transfer'
     */
    public function findActiveByCode(string $code): ?PaymentMethod;

    /**
     * Lấy tài khoản nhận tiền chuyển khoản đang Active.
     * UC-45 Luồng 3: Hiển thị thông tin chuyển khoản.
     */
    public function findActiveTransferAccount(): ?PaymentMethod;

    /**
     * Lấy tất cả phương thức (kể cả inactive) cho Admin quản lý.
     */
    public function getAllForAdmin(): Collection;
}
