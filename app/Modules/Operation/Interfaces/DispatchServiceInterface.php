<?php

declare(strict_types=1);

namespace App\Modules\Operation\Interfaces;

interface DispatchServiceInterface
{
    /**
     * Bắt đầu quy trình điều phối "Sóng ưu tiên" vòng 1 (Internal Fleet).
     * @param string $rideId
     */
    public function initiateDispatch(string $rideId): void;

    /**
     * Thực hiện vòng 2 của điều phối (Partner Drivers) sau khi hết Delay Window.
     * @param string $rideId
     */
    public function fallbackToPartnerDrivers(string $rideId): void;
}
