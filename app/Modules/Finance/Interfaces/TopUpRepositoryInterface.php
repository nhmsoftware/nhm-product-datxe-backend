<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\TopUp;

interface TopUpRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm top-up theo external ID (từ Payment Gateway)
     */
    public function findByExternalId(string $externalId): ?TopUp;

    /**
     * Tìm top-up theo ID và user_id (đảm bảo ownership).
     * UC-45: Xem chi tiết / hủy giao dịch của chính mình.
     */
    public function findByIdAndUser(string $id, string $userId): ?TopUp;

    /**
     * Lấy danh sách top-up của user có phân trang.
     * UC-45: Lịch sử nạp tiền.
     */
    public function getPaginatedByUser(string $userId, int $page, int $limit): array;

    /**
     * Kiểm tra xem có giao dịch pending nào đối với phương thức thanh toán này không.
     */
    public function hasPendingTopUps(string $paymentMethodCode): bool;
}

