<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\DTO\Admin\ListUsersDTO;
use App\Modules\User\DTO\Admin\UpdateUserStatusDTO;

interface AdminUserServiceInterface
{
    /**
     * Lấy danh sách khách hàng (UC-77).
     */
    public function listCustomers(ListUsersDTO $dto): ServiceReturn;

    /**
     * Xem chi tiết khách hàng (UC-77).
     */
    public function getCustomerDetail(string|int $userId): ServiceReturn;

    /**
     * Cập nhật trạng thái người dùng (Khóa/Mở khóa) (UC-78).
     */
    public function updateUserStatus(UpdateUserStatusDTO $dto): ServiceReturn;
}
