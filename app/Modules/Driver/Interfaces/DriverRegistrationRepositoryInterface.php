<?php

declare(strict_types=1);

namespace App\Modules\Driver\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Driver\Model\Enums\KycStatus;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\Driver\Model\UserReviewApplication;

interface DriverRegistrationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm hồ sơ KYC Pending/Approved của user.
     * UC-30 A9 (pending check), A14/A15 (sau xét duyệt).
     */
    public function findActiveApplicationByUser(string $userId, KycType $kycType): ?UserReviewApplication;

    /**
     * Kiểm tra CCCD đã được đăng ký trong hồ sơ Pending/Approved chưa.
     * UC-30 A6 — kết thúc use case nếu trùng.
     */
    public function existsByCitizenId(string $citizenId, string $excludeUserId = '0'): bool;

    /**
     * Kiểm tra biển số xe đã được đăng ký chưa.
     * UC-30 A7 — yêu cầu user nhập lại.
     */
    public function existsByVehicleNumber(string $vehicleNumber, string $excludeUserId = '0'): bool;

    /**
     * Tạo hồ sơ đăng ký tài xế trạng thái Pending.
     * UC-30 Normal Flow bước 17–18.
     */
    public function createDriverApplication(
        string  $userId,
        array   $snapshotData,
        KycType $kycType,
    ): UserReviewApplication;

    /**
     * Cập nhật trạng thái (Admin duyệt/từ chối — UC-30 A14).
     */
    public function updateStatus(
        string      $applicationId,
        KycStatus   $status,
        ?string     $cancelReason = null,
    ): bool;
}
