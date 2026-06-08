<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\Enums\DriverGroupType;
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
     * Tạo profile cho tài xế.
     */
    public function createDriverProfile(User $user, array $data): \App\Modules\User\Model\DriverProfile;

    /**
     * Upsert device
     * @param User $user
     * @param array $data
     * @return void
     */
    public function upsertDevice(User $user, array $data): void;

    /**
     * Xóa token thiết bị khi logout (UC-127)
     */
    public function clearDeviceToken(User $user, string $deviceId): bool;

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

    /**
     * Đếm tổng số lượng người dùng theo các vai trò.
     * @param array $roles Mảng các giá trị của UserRole
     */
    public function countUsersByRoles(array $roles): int;

    /**
     * Đếm số lượng cửa hàng đang hoạt động.
     */
    public function countActiveMerchants(): int;

    /**
     * Tìm driver kèm profile (UC-13).
     */
    public function findDriverWithProfileById(string $driverId): ?User;

    /**
     * Tìm danh sách khách hàng có phân trang và lọc (UC-77).
     */
    public function findCustomers(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Cập nhật trạng thái hoạt động (Khóa/Mở khóa) và các thông tin liên quan (UC-78/UC-77).
     */
    public function updateActiveStatus(string|int $userId, array $data): bool;

    /**
     * Tìm danh sách tài xế theo bộ lọc (UC-80).
     */
    public function findDrivers(array $filters, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Duyệt hồ sơ tài xế (UC-81).
     */
    public function approveDriverApplication(string|int $userId): bool;

    /**
     * Từ chối hồ sơ tài xế (UC-82).
     */
    public function rejectDriverApplication(string|int $userId, string $reason): bool;

    /**
     * Tìm chi tiết tài xế (UC-83).
     */
    public function findDriverDetailById(string|int $userId): ?User;

    /**
     * Cập nhật nhóm tài xế (UC-85).
     */
    public function updateDriverGroup(string|int $userId, DriverGroupType $groupType): bool;

    /**
     * Tìm chi tiết người dùng kèm profile (UC-77/UC-79).
     */
    public function findDetailById(string|int $userId): ?User;

    /**
     * Kiểm tra khách hàng có chuyến xe đang xử lý hay không.
     */
    public function hasActiveRide(string|int $userId): bool;

    /**
     * Kiểm tra khách hàng có đơn đồ ăn đang xử lý hay không.
     */
    public function hasActiveFoodOrder(string|int $userId): bool;

    /**
     * Xóa mềm khách hàng và các profile/liên kết liên quan.
     */
    public function softDeleteCustomer(User $user): void;

    /**
     * Kiểm tra tài xế có chuyến xe đang xử lý hay không.
     */
    public function hasActiveRideForDriver(string|int $userId): bool;

    /**
     * Kiểm tra tài xế có đơn đồ ăn đang xử lý hay không.
     */
    public function hasActiveFoodOrderForDriver(string|int $userId): bool;

    /**
     * Xóa mềm tài xế và các profile/liên kết liên quan.
     */
    public function softDeleteDriver(User $user): void;

    /**
     * Kiểm tra CCCD đã được sử dụng chưa (không tính bản thân).
     */
    public function isCitizenIdExists(string $citizenId, ?string $excludeUserId = null): bool;

    /**
     * Chunk active users.
     */
    public function chunkActiveUsers(int $chunkSize, callable $callback): void;
}
