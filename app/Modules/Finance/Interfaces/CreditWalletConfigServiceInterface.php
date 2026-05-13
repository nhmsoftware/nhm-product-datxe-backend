<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\UpdateCreditWalletConfigDTO;

interface CreditWalletConfigServiceInterface
{
    /**
     * Lấy cấu hình Credit Wallet hiện tại.
     */
    public function getConfig(): ServiceReturn;

    /**
     * Cập nhật cấu hình Credit Wallet.
     */
    public function updateConfig(UpdateCreditWalletConfigDTO $dto, int $adminId): ServiceReturn;
}
