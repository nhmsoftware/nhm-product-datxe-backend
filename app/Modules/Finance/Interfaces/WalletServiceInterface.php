<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\CancelTopUpDTO;
use App\Modules\Finance\DTO\GetTopUpDetailDTO;
use App\Modules\Finance\DTO\GetTopUpOptionsDTO;
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
     * Lấy danh sách phương thức nạp tiền khả dụng và số dư ví (UC-45)
     */
    public function getTopUpOptions(GetTopUpOptionsDTO $dto): ServiceReturn;

    /**
     * Initiate a top up session (UC-45)
     */
    public function initiateTopUp(InitiateTopUpDTO $dto): ServiceReturn;

    /**
     * Lấy chi tiết một giao dịch nạp tiền (UC-45)
     */
    public function getTopUpDetail(GetTopUpDetailDTO $dto): ServiceReturn;

    /**
     * Hủy giao dịch nạp tiền đang Pending (UC-45 A4)
     */
    public function cancelTopUp(CancelTopUpDTO $dto): ServiceReturn;

    /**
     * Process top up callback from payment gateway (UC-45 Callback)
     */
    public function processTopUpCallback(array $payload): ServiceReturn;
}

