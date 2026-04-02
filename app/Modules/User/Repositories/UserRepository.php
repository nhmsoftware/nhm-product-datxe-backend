<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserDevice;

class UserRepository extends BaseRepository implements UserRepositoryInterface
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

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->model
            ->where('google_id', $googleId)
            ->first();
    }

    public function findByAppleId(string $appleId): ?User
    {
        return $this->model
            ->where('apple_id', $appleId)
            ->first();
    }

    /**
     * Tạo user mới.
     */
    public function create(array $data): ?User
    {
        return $this->model->create($data);
    }

    /**
     * Tạo profile cho khách hàng.
     *
     */
    public function createCustomerProfile(int $userId, array $data): CustomerProfile
    {
        return CustomerProfile::create(array_merge($data, ['user_id' => $userId]));
    }

    /**
     * Upsert thiết bị của user.
     * - Nếu device_id đã tồn tại với user này → cập nhật token
     * - Nếu chưa → tạo mới
     */
    public function upsertDevice(int $userId, array $data): void
    {
        UserDevice::updateOrCreate(
            [
                'user_id'   => $userId,
                'device_id' => $data['device_id'],
            ],
            [
                'token'       => $data['token']       ?? null,
                'device_type' => $data['device_type'] ?? null,
            ]
        );
    }
}
