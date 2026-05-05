<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\DTO\Admin\ListDriversDTO;
use App\Modules\User\DTO\Admin\ApproveDriverDTO;
use App\Modules\User\DTO\Admin\RejectDriverDTO;
use App\Modules\User\DTO\Admin\UpdateDriverStatusDTO;
use App\Modules\User\DTO\Admin\AssignDriverGroupDTO;

interface AdminDriverServiceInterface
{
    /**
     * Lấy danh sách tài xế (UC-80).
     */
    public function listDrivers(ListDriversDTO $dto): ServiceReturn;

    /**
     * Duyệt tài xế (UC-81).
     */
    public function approveDriver(ApproveDriverDTO $dto): ServiceReturn;

    /**
     * Từ chối tài xế (UC-82).
     */
    public function rejectDriver(RejectDriverDTO $dto): ServiceReturn;

    /**
     * Chi tiết tài xế (UC-83).
     */
    public function getDriverDetail(string|int $userId): ServiceReturn;

    /**
     * Khóa/Mở khóa tài xế (UC-84).
     */
    public function updateStatus(UpdateDriverStatusDTO $dto): ServiceReturn;

    /**
     * Gán đội xe (UC-85).
     */
    public function assignDriverGroup(AssignDriverGroupDTO $dto): ServiceReturn;

    /**
     * Xuất dữ liệu tài xế.
     */
    public function exportDrivers(ListDriversDTO $dto): ServiceReturn;
}

