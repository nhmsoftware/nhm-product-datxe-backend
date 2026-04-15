<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\RewardHistoryDTO;
use App\Modules\Finance\Interfaces\RewardRepositoryInterface;
use App\Modules\Finance\Interfaces\RewardServiceInterface;
use App\Modules\Finance\Interfaces\RewardWalletRepositoryInterface;

final class RewardService extends BaseService implements RewardServiceInterface
{
    public function __construct(
        private readonly RewardRepositoryInterface $rewardRepository,
        private readonly RewardWalletRepositoryInterface $rewardWalletRepository,
    ) {}

    /**
     * Lấy tổng quan điểm thưởng (số dư, tổng nhận, tổng tiêu) (UC-24)
     */
    public function getRewardOverview(int $customerId): ServiceReturn
    {
        return $this->execute(function () use ($customerId): array {
            $wallet = $this->rewardWalletRepository->findByCustomerId($customerId);

            if (!$wallet) {
                // Tự động tạo ví nếu chưa có (Đảm bảo có data cơ bản trả về theo thiết kế)
                $wallet = $this->rewardWalletRepository->firstOrCreateWallet($customerId);
            }

            return [
                'current_balance' => $wallet->balance,
                'total_earned'    => $wallet->total_earned,
                'total_used'      => $wallet->total_used,
            ];
        });
    }


    /**
     * Lấy danh sách lịch sử giao dịch điểm (UC-24)
     */
    public function getHistory(RewardHistoryDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $paginator = $this->rewardRepository->getTransactionsPaginated($dto);

            $items = $paginator->map(function ($transaction) {
                return [
                    'id'          => $transaction->id,
                    'created_at'  => $transaction->created_at->format('Y-m-d H:i:s'),
                    'type'        => $transaction->type->value,
                    'type_label'  => $transaction->type->getLabel(),
                    'points'      => $transaction->points,
                    'description' => $transaction->description,
                ];
            })->toArray();

            return [
                'data' => $items,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ];
        });
    }

    /**
     * Lấy chi tiết một giao dịch điểm (UC-24-5)
     */
    public function getTransactionDetail(int $customerId, int $transactionId): ServiceReturn
    {
        return $this->execute(function () use ($customerId, $transactionId): array {
            $transaction = $this->rewardRepository->getTransactionDetail($transactionId, $customerId);

            $this->validate($transaction !== null, 'Không thể tải chi tiết giao dịch.', 404);

            return [
                'id'             => $transaction->id,
                'created_at'     => $transaction->created_at->format('Y-m-d H:i:s'),
                'type'           => $transaction->type->value,
                'type_label'     => $transaction->type->getLabel(),
                'points'         => $transaction->points,
                'description'    => $transaction->description,
                'reference_type' => $transaction->reference_type,
                'reference_id'   => $transaction->reference_id,
            ];
        });
    }
}
