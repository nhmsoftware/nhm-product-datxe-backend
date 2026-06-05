<?php

declare(strict_types=1);

namespace App\Modules\Driver\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\RegisterDriverSubmitDTO;
use App\Modules\Driver\DTO\ApproveRegistrationDTO;

interface DriverRegistrationServiceInterface
{
    /**
     * UC-30 nộp tài liệu → tạo hồ sơ Pending.
     * Alternative Flows: A3, A4, A8, A13.
     */
    public function submitRegistration(RegisterDriverSubmitDTO $dto): ServiceReturn;

    /**
     * Admin duyệt hồ sơ tài xế.
     */
    public function approveRegistration(ApproveRegistrationDTO $dto): ServiceReturn;

    /**
     * Lấy danh sách hồ sơ đăng ký đang chờ duyệt.
     */
    public function getApplications(): ServiceReturn;

    /**
     * Lấy chi tiết một hồ sơ kèm tài liệu.
     */
    public function getApplicationDetails(string $id): ServiceReturn;

    /**
     * Lấy danh sách đội xe (Driver Groups).
     */
    public function getDriverGroups(): ServiceReturn;

    /**
     * Lấy danh sách các dịch vụ tài xế có thể đăng ký (UC-30).
     *
     * @param int|null $vehicleTypeId  Nếu truyền vào → trả về chỉ các dịch vụ loại xe đó hỗ trợ.
     *                                 Nếu null → trả về toàn bộ dịch vụ kèm supported_vehicle_types.
     */
    public function getRegistrationServices(?int $vehicleTypeId = null): ServiceReturn;
}
