<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\Food\Model\FoodOrder;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\CustomerSavedAddress;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\KycType;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserDevice;

final class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function getModel(): string
    {
        return User::class;
    }

    /**
     * Tìm user theo số điện thoại (bao gồm soft-deleted để phát hiện tài khoản cũ).
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->getQuery()
            ->where('phone', $phone)
            ->withTrashed()
            ->first();
    }

    /**
     * Kiểm tra số điện thoại đã tồn tại chưa (kể cả soft-deleted).
     * Dùng để chặn đăng ký trùng số điện thoại.
     */
    public function existsByPhone(string $phone): bool
    {
        return $this->getQuery()
            ->where('phone', $phone)
            ->withTrashed()
            ->exists();
    }

    public function findByEmail(?string $email): ?User
    {
        if (!$email) {
            return null;
        }

        return $this->getQuery()
            ->where('email', $email)
            ->withTrashed()
            ->first();
    }

    /**
     * Check if user exists by google_id
     */
    public function existsByGoogleId(string $googleId): bool
    {
        return $this->getQuery()->where('google_id', $googleId)->exists();
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->getQuery()
            ->where('google_id', $googleId)
            ->first();
    }

    /**
     * Check if user exists by apple_id
     */
    public function existsByAppleId(string $appleId): bool
    {
        return $this->getQuery()->where('apple_id', $appleId)->exists();
    }

    public function findByAppleId(string $appleId): ?User
    {
        return $this->getQuery()
            ->where('apple_id', $appleId)
            ->first();
    }

    /**
     * Tạo profile cho khách hàng.
     *
     */
    public function createCustomerProfile(User $user, array $data): CustomerProfile
    {
        return $user->customerProfile()->create($data);
    }

    /**
     * @inheritDoc
     */
    public function createDriverProfile(User $user, array $data): \App\Modules\User\Model\DriverProfile
    {
        return $user->driverProfile()->create($data);
    }

    /**
     * Upsert thiết bị của user.
     * - Nếu device_id đã tồn tại với user này → cập nhật token
     * - Nếu chưa → tạo mới
     */
    public function upsertDevice(User $user, array $data): void
    {
        $user->userDevices()->updateOrCreate([
            'user_id'   => $user->id,
            'device_id' => $data['device_id'],
        ],[
            'token'       => $data['token'],
            'device_type' => $data['device_type'] ?? null,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function clearDeviceToken(User $user, string $deviceId): bool
    {
        return (bool) $user->userDevices()
            ->where('device_id', $deviceId)
            ->update(['token' => null]);
    }

    /**
     * Cập nhật vai trò của user.
     */
    public function updateRole(int|string $userId, UserRole $role): bool
    {
        return (bool) $this->getQuery()->where('id', $userId)->update([
            'role' => $role->value,
        ]);
    }

    /**
     * Đếm tổng số lượng người dùng theo các vai trò.
     */
    public function countUsersByRoles(array $roles): int
    {
        return $this->getQuery()->whereIn('role', $roles)->count();
    }

    /**
     * Đếm số lượng cửa hàng đang hoạt động.
     */
    public function countActiveMerchants(): int
    {
        return $this->getQuery()
            ->where('role', UserRole::Merchants->value)
            ->where('is_active', true)
            ->count();
    }

    /**
     * Tìm driver kèm profile (UC-13).
     */
    public function findDriverWithProfileById(string $driverId): ?User
    {
        /** @var User|null */
        return $this->getQuery()->with('driverProfile')
            ->where('id', $driverId)
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function findCustomers(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getQuery()->with('customerProfile')
            ->where('role', UserRole::Customer->value);

        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('phone', 'like', $keyword)
                  ->orWhere('email', 'like', $keyword)
                  ->orWhereHas('customerProfile', function ($qp) use ($keyword) {
                      $qp->where('full_name', 'like', $keyword);
                  });
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function updateActiveStatus(string|int $userId, array $data): bool
    {
        return (bool) $this->getQuery()->where('id', $userId)->update($data);
    }

    /**
     * @inheritDoc
     *
     * Quy tắc roles được đưa vào scope:
     *  - kyc_status không filter (tất cả): Driver + Customer có hồ sơ KYC tài xế (đang chờ hoặc bị từ chối)
     *  - kyc_status=1 (Pending):           Driver + Customer (chưa được nâng role)
     *  - kyc_status=3 (Rejected):          Driver + Customer (bị từ chối, vẫn ở role Customer)
     *  - kyc_status=2 (Approved):          Driver chỉ (đã được nâng role)
     *  - kyc_status=0 (Chưa nộp):          Driver chỉ (chưa nộp hồ sơ)
     */
    public function findDrivers(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $kycStatusValue = isset($filters['kyc_status']) && $filters['kyc_status'] !== ''
            ? (int) $filters['kyc_status']
            : null;

        // Customer có thể có hồ sơ đang chờ (1) hoặc bị từ chối (3) mà chưa được nâng role.
        // Khi không filter (null), cũng cần bao gồm Customer có hồ sơ để admin có thể tìm kiếm.
        $includeCustomers = $kycStatusValue === null || $kycStatusValue === 1 || $kycStatusValue === 3;

        $rolesInScope = $includeCustomers
            ? [UserRole::Customer->value, UserRole::Driver->value]
            : [UserRole::Driver->value];

        $query = $this->getQuery()
            ->with([
                'driverProfile',
                'customerProfile', // Cần thiết để accessor full_name trả đúng tên với Customer role
                'userReviewApplications' => function ($q) {
                    $q->where('kyc_type', KycType::Driver->value)->latest();
                },
            ])
            ->whereIn('role', $rolesInScope);

        // Khi không lọc kyc_status, Customer phải có ít nhất 1 hồ sơ KYC tài xế
        // để tránh hiện toàn bộ khách hàng trong danh sách tài xế.
        if ($kycStatusValue === null) {
            $query->where(function ($q) {
                $q->where('role', UserRole::Driver->value)
                  ->orWhereHas('userReviewApplications', function ($q2) {
                      $q2->where('kyc_type', KycType::Driver->value);
                  });
            });
        }

        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('phone', 'like', $keyword)
                  ->orWhere('email', 'like', $keyword)
                  ->orWhereHas('driverProfile', function ($qp) use ($keyword) {
                      $qp->where('full_name', 'like', $keyword);
                  })
                  ->orWhereHas('customerProfile', function ($qp) use ($keyword) {
                      $qp->where('full_name', 'like', $keyword);
                  });
            });
        }

        if ($kycStatusValue !== null) {
            if ($kycStatusValue === 0) {
                // Lọc những người chưa từng nộp hồ sơ
                $query->whereDoesntHave('userReviewApplications', function ($q) {
                    $q->where('kyc_type', KycType::Driver->value);
                });
            } else {
                // Lọc theo trạng thái cụ thể (Pending=1 / Approved=2 / Rejected=3)
                $query->whereHas('userReviewApplications', function ($q) use ($kycStatusValue) {
                    $q->where('kyc_type', KycType::Driver->value)
                      ->where('kyc_status', $kycStatusValue)
                      ->whereNotExists(function ($sub) {
                          $sub->selectRaw('1')
                              ->from('user_review_applications as sub_app')
                              ->whereColumn('sub_app.user_id', 'user_review_applications.user_id')
                              ->where('sub_app.kyc_type', KycType::Driver->value)
                              ->whereColumn('sub_app.created_at', '>', 'user_review_applications.created_at');
                      });
                });
            }
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['driver_group_type'])) {
            $query->whereHas('driverProfile', function ($q) use ($filters) {
                $q->where('driver_group_type', (int) $filters['driver_group_type']);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function approveDriverApplication(string|int $userId): bool
    {
        return (bool) $this->getQuery()->find($userId)
            ->userReviewApplications()
            ->where('kyc_type', KycType::Driver->value)
            ->where('kyc_status', KycStatus::Pending->value)
            ->latest()
            ->update(['kyc_status' => KycStatus::Approved->value]);
    }

    /**
     * @inheritDoc
     */
    public function rejectDriverApplication(string|int $userId, string $reason): bool
    {
        return (bool) $this->getQuery()->find($userId)
            ->userReviewApplications()
            ->where('kyc_type', KycType::Driver->value)
            ->where('kyc_status', KycStatus::Pending->value)
            ->latest()
            ->update([
                'kyc_status'    => KycStatus::Rejected->value,
                'cancel_reason' => $reason,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function findDriverDetailById(string|int $userId): ?User
    {
        return $this->getQuery()->with(['driverProfile', 'userReviewApplications' => function ($q) {
            $q->where('kyc_type', KycType::Driver->value)->latest();
        }])
            ->where('role', UserRole::Driver->value)
            ->find($userId);
    }

    /**
     * @inheritDoc
     */
    public function updateDriverGroup(string|int $userId, DriverGroupType $groupType): bool
    {
        return (bool) $this->getQuery()->find($userId)
            ->driverProfile()
            ->update([
                'driver_group_type' => $groupType->value,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function findDetailById(string|int $userId): ?User
    {
        /** @var User|null */
        return $this->getQuery()->with(['customerProfile', 'driverProfile'])
            ->where('id', $userId)
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function hasActiveRide(string|int $userId): bool
    {
        return Ride::query()
            ->where('customer_id', (string) $userId)
            ->whereIn('status', [
                RideStatus::PENDING->value,
                RideStatus::ACCEPTED->value,
                RideStatus::PICKED_UP->value,
                RideStatus::IN_PROGRESS->value,
                RideStatus::CANCELLATION_REQUESTED->value,
            ])
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function hasActiveFoodOrder(string|int $userId): bool
    {
        return FoodOrder::query()
            ->where('customer_id', (string) $userId)
            ->whereIn('status', [
                FoodOrderStatus::PENDING->value,
                FoodOrderStatus::CONFIRMED->value,
                FoodOrderStatus::PREPARING->value,
                FoodOrderStatus::READY->value,
                FoodOrderStatus::PICKED_UP->value,
            ])
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function softDeleteCustomer(User $user): void
    {
        $user->tokens()->delete();
        UserDevice::query()->where('user_id', $user->id)->delete();

        if ($user->customerProfile) {
            CustomerSavedAddress::query()
                ->where('customer_id', $user->customerProfile->id)
                ->delete();

            $user->customerProfile->delete();
        }

        $user->delete();
    }

    /**
     * @inheritDoc
     */
    public function hasActiveRideForDriver(string|int $userId): bool
    {
        return Ride::query()
            ->where('driver_id', (string) $userId)
            ->whereIn('status', [
                RideStatus::ACCEPTED->value,
                RideStatus::PICKED_UP->value,
                RideStatus::IN_PROGRESS->value,
                RideStatus::CANCELLATION_REQUESTED->value,
            ])
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function hasActiveFoodOrderForDriver(string|int $userId): bool
    {
        return FoodOrder::query()
            ->whereHas('ride', function ($q) use ($userId) {
                $q->where('driver_id', (string) $userId);
            })
            ->whereIn('status', [
                FoodOrderStatus::PENDING->value,
                FoodOrderStatus::CONFIRMED->value,
                FoodOrderStatus::PREPARING->value,
                FoodOrderStatus::READY->value,
                FoodOrderStatus::PICKED_UP->value,
            ])
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function softDeleteDriver(User $user): void
    {
        $user->tokens()->delete();
        UserDevice::query()->where('user_id', $user->id)->delete();

        if ($user->customerProfile) {
            CustomerSavedAddress::query()
                ->where('customer_id', $user->customerProfile->id)
                ->delete();

            $user->customerProfile->delete();
        }

        if ($user->driverProfile) {
            $user->driverProfile->delete();
        }

        $user->delete();
    }

    /**
     * @inheritDoc
     */
    public function isCitizenIdExists(string $citizenId, ?string $excludeUserId = null): bool
    {
        $query = $this->getQuery()->where('citizen_id', $citizenId);
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }
        return $query->exists();
    }

    /**
     * @inheritDoc
     */
    public function chunkActiveUsers(int $chunkSize, callable $callback): void
    {
        $this->getQuery()->where('is_active', true)->chunk($chunkSize, $callback);
    }
}
