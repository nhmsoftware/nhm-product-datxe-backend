<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\CreateSubscriptionPackageDTO;
use App\Modules\Finance\DTO\UpdateSubscriptionPackageDTO;

interface AdminSubscriptionServiceInterface
{
    /**
     * Lấy danh sách tất cả gói thuê bao (Admin) (UC-118)
     */
    public function listPackages(): ServiceReturn;

    /**
     * Tạo gói thuê bao mới (UC-118)
     */
    public function createPackage(CreateSubscriptionPackageDTO $dto): ServiceReturn;

    /**
     * Cập nhật gói thuê bao (UC-118)
     */
    public function updatePackage(string $packageId, UpdateSubscriptionPackageDTO $dto): ServiceReturn;

    /**
     * Vô hiệu hóa gói thuê bao (UC-118 - A5)
     * Gói đã mua vẫn có hiệu lực, chỉ ngừng bán cho tài xế mới.
     */
    public function disablePackage(string $packageId): ServiceReturn;
}
