<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\CancelTopUpDTO;
use App\Modules\Finance\DTO\GetTopUpDetailDTO;
use App\Modules\Finance\DTO\GetTopUpOptionsDTO;
use App\Modules\Finance\DTO\InitiateTopUpDTO;
use App\Modules\Finance\DTO\ManageWalletDTO;
use App\Modules\Finance\DTO\ViewCreditWalletDTO;
use App\Modules\Finance\DTO\WalletTransactionDetailDTO;
use App\Modules\Finance\Events\TopUpCompleted;
use App\Modules\Finance\Interfaces\CreditWalletConfigRepositoryInterface;
use App\Modules\Finance\Interfaces\FinanceRealtimeInterface;
use App\Modules\Finance\Interfaces\PaymentMethodRepositoryInterface;
use App\Modules\Finance\Interfaces\TopUpRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletServiceInterface;
use App\Modules\Finance\Interfaces\WalletTransactionRepositoryInterface;
use App\Modules\Finance\Model\Enums\PaymentMethodType;
use App\Modules\Finance\Model\Enums\TopUpStatus;
use App\Modules\Finance\Model\Enums\WalletTransactionType;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\DriverStatus;
use Illuminate\Support\Facades\Log;

final class WalletService extends BaseService implements WalletServiceInterface
{
    public function __construct(
        private readonly WalletRepositoryInterface             $walletRepository,
        private readonly WalletTransactionRepositoryInterface  $transactionRepository,
        private readonly DriverProfileRepositoryInterface      $driverProfileRepository,
        private readonly TopUpRepositoryInterface              $topUpRepository,
        private readonly FinanceRealtimeInterface              $realtimeService,
        private readonly CreditWalletConfigRepositoryInterface $walletConfigRepository,
        private readonly PaymentMethodRepositoryInterface      $paymentMethodRepository,
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
            $totalUsed  = $this->transactionRepository->getTotalUsed($wallet->id);

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
                ],
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
     * UC-45: Lấy màn hình nạp tiền — số dư + phương thức khả dụng + mệnh giá gợi ý.
     */
    public function getTopUpOptions(GetTopUpOptionsDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $wallet  = $this->walletRepository->firstOrCreateForUser($dto->userId);
            $methods = $this->paymentMethodRepository->getActiveMethods();

            return [
                'balance'           => (float) $wallet->balance,
                'suggested_amounts' => [50000, 100000, 200000, 500000],
                'payment_methods'   => $methods->map(fn($m) => [
                    'code'       => $m->code,
                    'name'       => $m->name,
                    'type'       => $m->type->value,
                    'type_label' => $m->type->getLabel(),
                    'min_amount' => (float) $m->min_amount,
                    'max_amount' => (float) $m->max_amount,
                    'icon_url'   => $m->icon_url,
                ])->values()->toArray(),
            ];
        });
    }

    /**
     * UC-45: Tạo yêu cầu nạp tiền.
     * - Validate phương thức từ DB (không hardcode).
     * - Validate số tiền theo min/max của từng phương thức.
     * - Luồng bank_transfer trả về transfer_info (số TK, QR, nội dung CK).
     * - Luồng e_wallet / bank_card trả về redirect_url.
     */
    public function initiateTopUp(InitiateTopUpDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Kiểm tra ví
            $wallet = $this->walletRepository->findByUserId($dto->userId);
            $this->validate($wallet !== null, 'Không tìm thấy ví.', 404);

            // 2. A3: Kiểm tra phương thức thanh toán Active (từ DB, không hardcode)
            $paymentMethod = $this->paymentMethodRepository->findActiveByCode($dto->paymentMethodCode);
            $this->validate(
                $paymentMethod !== null,
                'Phương thức thanh toán hiện không khả dụng. Vui lòng chọn phương thức khác.',
                400
            );

            // 3. A1: Validate số tiền theo min/max của phương thức cụ thể
            $this->validate(
                $dto->amount >= $paymentMethod->min_amount,
                'Số tiền nạp tối thiểu là ' . number_format($paymentMethod->min_amount, 0, ',', '.') . 'đ.',
                400
            );
            $this->validate(
                $dto->amount <= $paymentMethod->max_amount,
                'Số tiền nạp tối đa là ' . number_format($paymentMethod->max_amount, 0, ',', '.') . 'đ.',
                400
            );

            // 4. Luồng 3 — Bank Transfer: kiểm tra tài khoản nhận tiền được cấu hình
            if ($paymentMethod->type === PaymentMethodType::BANK_TRANSFER) {
                $transferAccount = $this->paymentMethodRepository->findActiveTransferAccount();
                $this->validate(
                    $transferAccount !== null && !empty($transferAccount->transfer_info),
                    'Phương thức chuyển khoản trực tiếp hiện chưa khả dụng.',
                    400
                );
            }

            // 5. Tính expired_at theo loại phương thức (Business Rule #5)
            // e-wallet (MoMo, ZaloPay): 30 phút | bank_transfer (payOS): 24 giờ
            $expiredAt = $paymentMethod->type === PaymentMethodType::BANK_TRANSFER
                ? now()->addHours(24)
                : now()->addMinutes(30);

            // 6. Tạo TopUp session với status Pending — mã giao dịch duy nhất (Business Rule)
            $externalId = 'TX-' . strtoupper(substr($dto->paymentMethodCode, 0, 3)) . '-' . strtoupper(uniqid());
            $topUp = $this->topUpRepository->create([
                'user_id'        => $dto->userId,
                'wallet_id'      => $wallet->id,
                'amount'         => $dto->amount,
                'status'         => TopUpStatus::PENDING,
                'payment_method' => $dto->paymentMethodCode,
                'external_id'    => $externalId,
                'expired_at'     => $expiredAt,
            ]);

            // 7. Cấu trúc response theo luồng
            $response = [
                'top_up_id'      => (string) $topUp->id,
                'external_id'    => $topUp->external_id,
                'amount'         => (float) $topUp->amount,
                'payment_method' => $topUp->payment_method,
                'status'         => TopUpStatus::PENDING->value,
                'status_label'   => TopUpStatus::PENDING->getLabel(),
                'expired_at'     => $expiredAt->toIso8601String(),
            ];

            if ($paymentMethod->type === PaymentMethodType::BANK_TRANSFER) {
                // Luồng 3: Trả về thông tin chuyển khoản + nội dung CK có mã TX để đối soát
                $transferInfo = $paymentMethod->transfer_info ?? [];
                $response['transfer_info'] = [
                    'bank_name'        => $transferInfo['bank_name'] ?? '',
                    'account_number'   => $transferInfo['account_number'] ?? '',
                    'account_name'     => $transferInfo['account_name'] ?? '',
                    'bank_code'        => $transferInfo['bank_code'] ?? '',
                    'qr_url'           => $transferInfo['qr_url'] ?? null,
                    'transfer_content' => 'NAPTIEN ' . $externalId, // Business Rule: chứa mã TX
                    'amount'           => (float) $topUp->amount,
                ];
            } else {
                // Luồng 1 & 2: Trả về redirect URL (mock — tích hợp gateway thực tế sau)
                $response['redirect_url'] = 'https://mock-payment-gateway.com/pay'
                    . '?ref=' . $externalId
                    . '&amount=' . (int) $dto->amount
                    . '&method=' . $dto->paymentMethodCode;
            }

            return $response;
        }, useTransaction: true);
    }

    /**
     * UC-45: Xem chi tiết giao dịch nạp tiền.
     */
    public function getTopUpDetail(GetTopUpDetailDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $topUp = $this->topUpRepository->findByIdAndUser($dto->topUpId, $dto->userId);
            $this->validate($topUp !== null, 'Không tìm thấy giao dịch nạp tiền.', 404);

            return $this->formatTopUp($topUp);
        });
    }

    /**
     * UC-45 A4: Driver hủy giao dịch nạp tiền đang Pending.
     */
    public function cancelTopUp(CancelTopUpDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $topUp = $this->topUpRepository->findByIdAndUser($dto->topUpId, $dto->userId);
            $this->validate($topUp !== null, 'Không tìm thấy giao dịch nạp tiền.', 404);

            $this->validate(
                $topUp->status === TopUpStatus::PENDING,
                'Chỉ có thể hủy giao dịch đang ở trạng thái Đang xử lý.',
                400
            );

            $this->topUpRepository->updateById($topUp->id, [
                'status' => TopUpStatus::CANCELLED,
            ]);

            return [
                'top_up_id' => (string) $topUp->id,
                'status'    => TopUpStatus::CANCELLED->value,
                'message'   => 'Giao dịch nạp tiền đã được hủy.',
            ];
        }, useTransaction: true);
    }

    /**
     * UC-45: Xử lý callback từ Payment Gateway.
     * Idempotent: chỉ xử lý giao dịch ở trạng thái PENDING.
     * Tránh cộng tiền 2 lần (Business Rule #3).
     */
    public function processTopUpCallback(array $payload): ServiceReturn
    {
        return $this->execute(function () use ($payload): array {
            $externalId = $payload['external_id'] ?? null;
            $this->validate($externalId !== null, 'Thiếu mã giao dịch.', 400);

            $topUp = $this->topUpRepository->findByExternalId($externalId);
            $this->validate($topUp !== null, 'Không tìm thấy giao dịch nạp tiền.', 404);

            // Idempotency guard — Business Rule #3: tránh cộng tiền 2 lần
            if ($topUp->status !== TopUpStatus::PENDING) {
                return [
                    'status'  => $topUp->status->value,
                    'message' => 'Giao dịch đã được xử lý trước đó.',
                    'wallet'  => [],
                ];
            }

            // Xác định kết quả từ Gateway
            $gatewayStatus = strtolower($payload['status'] ?? 'success');

            // A9: Gateway báo expired (payOS timeout)
            if ($gatewayStatus === 'expired') {
                $this->topUpRepository->updateById($topUp->id, [
                    'status'   => TopUpStatus::EXPIRED,
                    'metadata' => $payload,
                ]);
                return [
                    'status'  => TopUpStatus::EXPIRED->value,
                    'message' => 'Giao dịch đã hết hạn.',
                    'wallet'  => [],
                ];
            }

            // A5/A6/A7/A8: Failed or Cancelled
            $isFailed = in_array($gatewayStatus, ['failed', 'cancelled', 'error'], true);
            if ($isFailed) {
                $this->topUpRepository->updateById($topUp->id, [
                    'status'   => $gatewayStatus === 'cancelled' ? TopUpStatus::CANCELLED : TopUpStatus::FAILED,
                    'metadata' => $payload,
                ]);
                return [
                    'status'  => $gatewayStatus === 'cancelled' ? TopUpStatus::CANCELLED->value : TopUpStatus::FAILED->value,
                    'message' => 'Giao dịch nạp tiền thất bại.',
                    'wallet'  => [],
                ];
            }

            // A12: Số tiền thanh toán không khớp — không cộng tiền, ghi log để đối soát
            if (isset($payload['amount'])) {
                $paidAmount = (float) $payload['amount'];
                if (abs($paidAmount - (float) $topUp->amount) > 0.01) {
                    Log::warning('UC-45 A12: Amount mismatch detected', [
                        'top_up_id'    => (string) $topUp->id,
                        'expected'     => (float) $topUp->amount,
                        'received'     => $paidAmount,
                        'external_id'  => $externalId,
                    ]);
                    $this->topUpRepository->updateById($topUp->id, [
                        'metadata' => array_merge($payload, ['mismatch' => true]),
                    ]);
                    return [
                        'status'  => TopUpStatus::PENDING->value,
                        'message' => 'Giao dịch cần được kiểm tra lại.',
                        'wallet'  => [],
                    ];
                }
            }

            // 1. Cập nhật TopUp → SUCCESS
            $this->topUpRepository->updateById($topUp->id, [
                'status'   => TopUpStatus::SUCCESS,
                'metadata' => $payload,
            ]);

            // 2. Cập nhật số dư ví
            $wallet        = $this->walletRepository->find($topUp->wallet_id);
            $balanceBefore = (float) $wallet->balance;
            $balanceAfter  = $balanceBefore + (float) $topUp->amount;

            $this->walletRepository->updateById($wallet->id, [
                'balance' => $balanceAfter,
            ]);

            // 3. UC-117: Đồng bộ trạng thái dispatch tài xế
            $this->syncDriverDispatchStatus((string) $topUp->user_id, $balanceAfter);

            // 4. Ghi WalletTransaction
            $this->transactionRepository->create([
                'wallet_id'      => $wallet->id,
                'type'           => WalletTransactionType::TOP_UP,
                'amount'         => (float) $topUp->amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => 'Nạp tiền qua ' . strtoupper($topUp->payment_method),
                'reference_type' => 'TopUp',
                'reference_id'   => $topUp->id,
            ]);

            // 5. Phát Domain Event → Listener → Redis → Socket.io
            event(new TopUpCompleted(
                topUpId:       (string) $topUp->id,
                userId:        (string) $topUp->user_id,
                amount:        (float) $topUp->amount,
                balanceAfter:  $balanceAfter,
                paymentMethod: $topUp->payment_method,
            ));

            return [
                'status'  => TopUpStatus::SUCCESS->value,
                'message' => 'Nạp tiền thành công.',
                'wallet'  => [
                    'balance' => $balanceAfter,
                ],
            ];
        }, useTransaction: true);
    }

    /**
     * UC-117: Đồng bộ trạng thái Dispatch dựa trên số dư ví.
     */
    private function syncDriverDispatchStatus(string $userId, float $currentBalance): void
    {
        $driver = $this->driverProfileRepository->findByUserId($userId);
        if (!$driver || $driver->driver_group_type === DriverGroupType::INTERNAL->value) {
            return;
        }

        $config = $this->walletConfigRepository->getLatestConfig();
        if (!$config->auto_lock) {
            return;
        }

        if ($currentBalance < $config->min_balance) {
            if ($driver->status === DriverStatus::ACTIVE) {
                $this->driverProfileRepository->updateStatus($driver->id, DriverStatus::DISPATCH_LOCKED);
                Log::info("UC-117: Driver {$userId} locked due to low balance ({$currentBalance} < {$config->min_balance})");
            }
        } else {
            if ($driver->status === DriverStatus::DISPATCH_LOCKED) {
                $this->driverProfileRepository->updateStatus($driver->id, DriverStatus::ACTIVE);
                Log::info("UC-117: Driver {$userId} unlocked as balance is sufficient ({$currentBalance} >= {$config->min_balance})");
            }
        }
    }

    /**
     * Format TopUp record cho response.
     */
    private function formatTopUp($topUp): array
    {
        return [
            'id'             => (string) $topUp->id,
            'amount'         => (float) $topUp->amount,
            'status'         => $topUp->status->value,
            'status_label'   => $topUp->status->getLabel(),
            'payment_method' => $topUp->payment_method,
            'external_id'    => $topUp->external_id,
            'created_at'     => $topUp->created_at->toIso8601String(),
            'updated_at'     => $topUp->updated_at->toIso8601String(),
        ];
    }

    /**
     * Helper: format transaction for list display.
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
