<?php

declare(strict_types=1);

namespace App\Modules\Driver\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Model\Enums\KycStatus;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\Driver\Model\UserReviewApplication;

final class DriverRegistrationRepository extends BaseRepository implements DriverRegistrationRepositoryInterface
{
    public function getModel(): string
    {
        return UserReviewApplication::class;
    }

    /**
     * Tìm hồ sơ KYC Pending/Approved của user.
     * UC-30 A9 (check pending app), A14/A15 (sau xét duyệt).
     */
    public function findActiveApplicationByUser(int|string $userId, KycType $kycType): ?UserReviewApplication
    {
        /** @var UserReviewApplication|null */
        return $this->model
            ->where('user_id', $userId)
            ->where('kyc_type', $kycType->value)
            ->whereIn('kyc_status', [KycStatus::PENDING->value, KycStatus::APPROVED->value])
            ->latest('created_at')
            ->first();
    }

    /**
     * Kiểm tra CCCD đã tồn tại trong snapshot_data của hồ sơ nào chưa.
     * Dùng PostgreSQL JSONB operator ->>' để query.
     * UC-30 A6.
     */
    public function existsByCitizenId(string $citizenId, int|string $excludeUserId = '0'): bool
    {
        return $this->model
            ->where('kyc_type', KycType::DRIVER->value)
            ->whereIn('kyc_status', [KycStatus::PENDING->value, KycStatus::APPROVED->value])
            ->where('user_id', '!=', $excludeUserId)
            ->whereRaw("snapshot_data->>'citizen_id' = ?", [$citizenId])
            ->exists();
    }

    /**
     * Kiểm tra biển số xe đã được đăng ký chưa.
     * UC-30 A7.
     */
    public function existsByVehicleNumber(string $vehicleNumber, int|string $excludeUserId = '0'): bool
    {
        return $this->model
            ->where('kyc_type', KycType::DRIVER->value)
            ->whereIn('kyc_status', [KycStatus::PENDING->value, KycStatus::APPROVED->value])
            ->where('user_id', '!=', $excludeUserId)
            ->whereRaw("snapshot_data->>'vehicle_number' = ?", [$vehicleNumber])
            ->exists();
    }

    /**
     * Tạo hồ sơ đăng ký tài xế trạng thái Pending.
     * UC-30 Normal Flow bước 17–18.
     */
    public function createDriverApplication(
        string  $userId,
        array   $snapshotData,
        KycType $kycType,
    ): UserReviewApplication {
        /** @var UserReviewApplication */
        return $this->model->create([
            'user_id'       => $userId,
            'snapshot_data' => $snapshotData,
            'kyc_type'      => $kycType->value,
            'kyc_status'    => KycStatus::PENDING->value,
        ]);
    }

    /**
     * Cập nhật trạng thái hồ sơ — Admin duyệt/từ chối (UC-30 A14).
     */
    public function updateStatus(int|string $applicationId, KycStatus $status, ?string $cancelReason = null): bool
    {
        return (bool) $this->model->where('id', $applicationId)->update([
            'kyc_status'    => $status->value,
            'cancel_reason' => $cancelReason,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPendingApplications(): \Illuminate\Support\Collection
    {
        return $this->model
            ->where('kyc_status', KycStatus::PENDING->value)
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function findByIdWithUser(int|string $applicationId): ?UserReviewApplication
    {
        /** @var UserReviewApplication|null */
        return $this->model->with('user')->find($applicationId);
    }
}
