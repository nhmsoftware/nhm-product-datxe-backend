<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\Finance\Model\CreditWalletConfig;

interface CreditWalletConfigRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Lấy cấu hình Credit Wallet hiện tại (duy nhất).
     */
    public function getLatestConfig(): CreditWalletConfig;

    /**
     * Cập nhật cấu hình Credit Wallet.
     */
    public function updateConfig(array $data): bool;
}
