<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;

interface AdminPaymentMethodServiceInterface
{
    /**
     * Lấy tất cả phương thức thanh toán cho Admin quản lý.
     */
    public function index(): ServiceReturn;

    /**
     * Tạo phương thức thanh toán mới.
     */
    public function store(array $data, string $adminId): ServiceReturn;

    /**
     * Cập nhật phương thức thanh toán.
     */
    public function update(string $id, array $data, string $adminId, bool $confirm = false): ServiceReturn;

    /**
     * Bật/tắt phương thức thanh toán (toggle is_active).
     */
    public function toggle(string $id, string $adminId, bool $confirm = false): ServiceReturn;
}
