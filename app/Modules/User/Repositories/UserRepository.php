<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\KycType;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;

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
        return $this->model
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
        return $this->model
            ->where('phone', $phone)
            ->withTrashed()
            ->exists();
    }

    public function findByEmail(?string $email): ?User
    {
        if (!$email) {
            return null;
        }

        return $this->model
            ->where('email', $email)
            ->withTrashed()
            ->first();
    }

    /**
     * Check if user exists by google_id
     */
    public function existsByGoogleId(string $googleId): bool
    {
        return $this->model->where('google_id', $googleId)->exists();
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->model
            ->where('google_id', $googleId)
            ->first();
    }

    /**
     * Check if user exists by apple_id
     */
    public function existsByAppleId(string $appleId): bool
    {
        return $this->model->where('apple_id', $appleId)->exists();
    }

    public function findByAppleId(string $appleId): ?User
    {
        return $this->model
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
     * Cập nhật vai trò của user.
     */
    public function updateRole(int|string $userId, UserRole $role): bool
    {
        return (bool) $this->model->where('id', $userId)->update([
            'role' => $role->value,
        ]);
    }

    /**
     * Đếm tổng số lượng người dùng theo các vai trò.
     */
    public function countUsersByRoles(array $roles): int
    {
        return $this->model->whereIn('role', $roles)->count();
    }

    /**
     * Đếm số lượng cửa hàng đang hoạt động.
     */
    public function countActiveMerchants(): int
    {
        return $this->model
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
        return $this->model->with('driverProfile')
            ->where('id', $driverId)
            ->first();
    }

    /**
     * @inheritDoc
     */
    public function findCustomers(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with('customerProfile')
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
        return (bool) $this->model->where('id', $userId)->update($data);
    }

    /**
     * @inheritDoc
     */
    public function findDrivers(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->model->with(['driverProfile', 'userReviewApplications' => function ($q) {
            $q->where('kyc_type', KycType::Driver->value)->latest();
        }])
            ->where('role', UserRole::Driver->value);

        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('phone', 'like', $keyword)
                  ->orWhere('email', 'like', $keyword)
                  ->orWhereHas('driverProfile', function ($qp) use ($keyword) {
                      $qp->where('full_name', 'like', $keyword);
                  });
            });
        }

        if (!empty($filters['kyc_status'])) {
            $query->whereHas('userReviewApplications', function ($q) use ($filters) {
                $q->where('kyc_type', KycType::Driver->value)
                  ->where('kyc_status', $filters['kyc_status'])
                  ->whereIn('id', function ($sub) {
                      $sub->selectRaw('max(id)')
                          ->from('user_review_applications')
                          ->where('kyc_type', KycType::Driver->value)
                          ->groupBy('user_id');
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
    public function approveDriverApplication(string|int $userId): bool
    {
        return (bool) $this->model->find($userId)
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
        return (bool) $this->model->find($userId)
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
        return $this->model->with(['driverProfile', 'userReviewApplications' => function ($q) {
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
        return (bool) $this->model->find($userId)
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
        return $this->model->with(['customerProfile', 'driverProfile'])
            ->where('id', $userId)
            ->first();
    }
}
