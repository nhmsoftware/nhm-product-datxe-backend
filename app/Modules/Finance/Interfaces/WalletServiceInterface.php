<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\ManageWalletDTO;
use App\Modules\Finance\DTO\ViewCreditWalletDTO;
use App\Modules\Finance\DTO\WalletTransactionDetailDTO;
use App\Modules\Finance\DTO\InitiateTopUpDTO;

interface WalletServiceInterface
{
    /**
     * Get data for Driver Manage Wallet dashboard (UC-43)
     */
    public function getManageWalletData(ManageWalletDTO $dto): ServiceReturn;

    /**
     * View Credit Wallet details (UC-44)
     */
    public function viewCreditWallet(ViewCreditWalletDTO $dto): ServiceReturn;

    /**
     * Get transaction detail (UC-44 detail)
     */
    public function getTransactionDetail(WalletTransactionDetailDTO $dto): ServiceReturn;

    /**
     * Initiate a top up session (UC-45)
     */
    public function initiateTopUp(InitiateTopUpDTO $dto): ServiceReturn;

    /**
     * Process top up callback from payment gateway (UC-45 Callback)
     */
    public function processTopUpCallback(array $payload): ServiceReturn;
}
