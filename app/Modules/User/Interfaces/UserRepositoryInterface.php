<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tìm kiếm user theo số điện thoại
     * @param string $phone
     * @return User|null
     */
    public function findByPhone(string $phone): ?User;

    /**
     * Kiểm tra số điện thoại đã tồn tại chưa (kể cả soft-deleted).
     * @param string $phone
     * @return bool
     */
    public function existsByPhone(string $phone): bool;

    /**
     * Tạo profile cho khách hàng
     * @param User $user
     * @param array $data
     * @return CustomerProfile
     */
    public function createCustomerProfile(User $user, array $data): CustomerProfile;

    /**
     * Upsert device
     * @param User $user
     * @param array $data
     * @return void
     */
    public function upsertDevice(User $user, array $data): void;

    /**
     * Tìm user theo googleId
     * @param string $googleId
     * @return User|null
     */
    public function findByGoogleId(string $googleId): ?User;

    /**
     * Tìm kiếm user theo appleId
     * @param string $appleId
     * @return User|null
     */
    public function findByAppleId(string $appleId): ?User;

    /**
     * Tìm kiếm user theo email
     * @param string|null $email
     * @return User|null
     */
    public function findByEmail(?string $email): ?User;

    /**
     * Check if user exists by google_id
     */
    public function existsByGoogleId(string $googleId): bool;

    /**
     * Check if user exists by apple_id
     */
    public function existsByAppleId(string $appleId): bool;

    /**
     * Cập nhật vai trò của user.
     */
    public function updateRole(int|string $userId, UserRole $role): bool;
}
