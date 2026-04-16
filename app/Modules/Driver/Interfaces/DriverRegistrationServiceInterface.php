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
}
