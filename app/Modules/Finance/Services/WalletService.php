<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\InitiateTopUpDTO;
use App\Modules\Finance\DTO\ManageWalletDTO;
use App\Modules\Finance\DTO\ViewCreditWalletDTO;
use App\Modules\Finance\DTO\WalletTransactionDetailDTO;
use App\Modules\Finance\Interfaces\FinanceRealtimeInterface;
use App\Modules\Finance\Interfaces\TopUpRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletServiceInterface;
use App\Modules\Finance\Interfaces\WalletTransactionRepositoryInterface;
use App\Modules\Finance\Model\Enums\WalletTransactionType;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;

final class WalletService extends BaseService implements WalletServiceInterface
{
    public function __construct(
        private readonly WalletRepositoryInterface            $walletRepository,
        private readonly WalletTransactionRepositoryInterface $transactionRepository,
        private readonly DriverProfileRepositoryInterface     $driverProfileRepository,
        private readonly TopUpRepositoryInterface             $topUpRepository,
        private readonly FinanceRealtimeInterface             $realtimeService,
    ) {}

    /**
     * UC-43: Get Manage Wallet data for Driver
     */
    public function getManageWalletData(ManageWalletDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $wallet = $this->walletRepository->firstOrCreateForUser($dto->userId);

            $driverProfile = $this->driverProfileRepository->findByUserId($dto->userId);
            $this->validate($driverProfile !== null, 'Không tìm thấy hồ sơ tài xế.', 404);

            $recentTransactions = $this->transactionRepository->getRecentByWalletId($wallet->id, 5);

            return [
                'driver_status' => [
                    'is_online' => (bool) $driverProfile->is_online,
                    'label'     => $driverProfile->is_online ? 'Trực tuyến' : 'Ngoại tuyến',
                ],
                'wallet' => [
                    'id'              => (string) $wallet->id,
                    'balance'         => (float) $wallet->balance,
                    'total_earned'    => (float) $wallet->total_earned,
                    'total_withdrawn' => (float) $wallet->total_withdrawn,
                ],
                'recent_transactions' => $recentTransactions->map(fn($tx) => $this->formatTransaction($tx))->toArray(),
            ];
        }, useTransaction: true);
    }

    /**
     * UC-44: View Credit Wallet details
     */
    public function viewCreditWallet(ViewCreditWalletDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $wallet = $this->walletRepository->findByUserId($dto->userId);
            $this->validate($wallet !== null, 'Không tìm thấy ví.', 404);

            $totalTopUp = $this->transactionRepository->getTotalTopUp($wallet->id);
            $totalUsed = $this->transactionRepository->getTotalUsed($wallet->id);

            $paginated = $this->transactionRepository->paginateByWalletId($wallet->id, $dto->page, $dto->limit);

            return [
                'wallet' => [
                    'balance'      => (float) $wallet->balance,
                    'total_top_up' => $totalTopUp,
                    'total_used'   => $totalUsed,
                ],
                'history' => [
                    'data' => collect($paginated['data'])->map(fn($tx) => $this->formatTransaction($tx))->toArray(),
                    'meta' => $paginated['meta'],
                ]
            ];
        });
    }

    /**
     * UC-44: Get transaction detail
     */
    public function getTransactionDetail(WalletTransactionDetailDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $wallet = $this->walletRepository->findByUserId($dto->userId);
            $this->validate($wallet !== null, 'Không tìm thấy ví.', 404);

            $tx = $this->transactionRepository->findByIdAndWallet($dto->transactionId, $wallet->id);
            $this->validate($tx !== null, 'Không tìm thấy giao dịch hoặc bạn không có quyền xem.', 404);

            return [
                'id'             => (string) $tx->id,
                'amount'         => (float) $tx->amount,
                'type'           => $tx->type->value,
                'type_label'     => $tx->type->getLabel(),
                'symbol'         => $tx->type->getSymbol(),
                'balance_before' => (float) $tx->balance_before,
                'balance_after'  => (float) $tx->balance_after,
                'description'    => $tx->description,
                'reference_type' => $tx->reference_type,
                'reference_id'   => (string) $tx->reference_id,
                'created_at'     => $tx->created_at->toIso8601String(),
                'status'         => 'Thành công',
            ];
        });
    }

    /**
     * UC-45: Initiate a top up session
     */
    public function initiateTopUp(InitiateTopUpDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $wallet = $this->walletRepository->findByUserId($dto->userId);
            $this->validate($wallet !== null, 'Không tìm thấy ví.', 404);

            // Create TopUp session
            $topUp = $this->topUpRepository->create([
                'user_id'        => $dto->userId,
                'wallet_id'      => $wallet->id,
                'amount'         => $dto->amount,
                'status'         => 'pending',
                'payment_method' => $dto->paymentMethod,
                'external_id'   => 'TX-' . uniqid(),
            ]);

            // Mock redirect URL to payment gateway
            $redirectUrl = "https://mock-payment-gateway.com/pay?id=" . $topUp->external_id;

            return [
                'top_up_id'    => (string) $topUp->id,
                'external_id'  => $topUp->external_id,
                'redirect_url' => $redirectUrl,
            ];
        }, useTransaction: true);
    }

    /**
     * UC-45: Process top up callback (Mocking success)
     */
    public function processTopUpCallback(array $payload): ServiceReturn
    {
        return $this->execute(function () use ($payload): array {
            $externalId = $payload['external_id'] ?? null;
            $this->validate($externalId !== null, 'Thiếu mã giao dịch.', 400);

            $topUp = $this->topUpRepository->findByExternalId($externalId);
            $this->validate($topUp !== null, 'Không tìm thấy giao dịch nạp tiền.', 404);
            $this->validate($topUp->status === 'pending', 'Giao dịch đã được xử lý trước đó.', 400);

            // 1. Update TopUp status
            $this->topUpRepository->updateById($topUp->id, [
                'status'   => 'success',
                'metadata' => $payload,
            ]);

            // 2. Update Wallet balance
            $wallet = $this->walletRepository->find($topUp->wallet_id);
            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + (float) $topUp->amount;

            $this->walletRepository->updateById($wallet->id, [
                'balance' => $balanceAfter,
            ]);

            // 3. Create Wallet Transaction
            $this->transactionRepository->create([
                'wallet_id'      => $wallet->id,
                'type'           => WalletTransactionType::TOP_UP,
                'amount'         => (float) $topUp->amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => "Nạp tiền qua " . strtoupper($topUp->payment_method),
                'reference_type' => 'TopUp',
                'reference_id'   => $topUp->id,
            ]);

            // 4. Broadcast Realtime Event
            $this->realtimeService->publishWalletEvent([
                'event'       => 'wallet.updated',
                'user_id'     => (string) $topUp->user_id,
                'balance'     => $balanceAfter,
                'amount'      => (float) $topUp->amount,
                'type'        => 'top_up',
                'occurred_at' => now()->toIso8601String(),
            ]);

            return [
                'status'  => 'success',
                'message' => 'Nạp tiền thành công.',
                'wallet'  => [
                    'balance' => $balanceAfter,
                ],
            ];
        }, useTransaction: true);
    }

    /**
     * Helper to format transaction for list display
     */
    private function formatTransaction($tx): array
    {
        return [
            'id'          => (string) $tx->id,
            'type'        => $tx->type->value,
            'type_label'  => $tx->type->getLabel(),
            'amount'      => (float) $tx->amount,
            'symbol'      => $tx->type->getSymbol(),
            'description' => $tx->description,
            'created_at'  => $tx->created_at->toIso8601String(),
        ];
    }
}
