<?php

declare(strict_types=1);

namespace App\Modules\Driver\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\RegisterDriverInitiateDTO;
use App\Modules\Driver\DTO\RegisterDriverSubmitDTO;

interface DriverRegistrationServiceInterface
{
    /**
     * UC-30 Bước 1 — Validate thông tin cá nhân + phương tiện → gửi OTP.
     * Normal Flow bước 4–13. Alternative Flows: A1, A2, A6, A7, A9, A12.
     */
    public function initiateRegistration(RegisterDriverInitiateDTO $dto): ServiceReturn;

    /**
     * UC-30 Bước 2 — Xác thực OTP + upload tài liệu → tạo hồ sơ Pending.
     * Normal Flow bước 14–19. Alternative Flows: A3, A4, A8, A10, A11, A13.
     */
    public function submitRegistration(RegisterDriverSubmitDTO $dto): ServiceReturn;
}
