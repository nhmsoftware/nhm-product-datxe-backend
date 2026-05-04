<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Finance\DTO\RegisterSubscriptionDTO;
use App\Modules\Finance\Interfaces\DriverSubscriptionRepositoryInterface;
use App\Modules\Finance\Interfaces\FinanceRealtimeInterface;
use App\Modules\Finance\Interfaces\SubscriptionPackageRepositoryInterface;
use App\Modules\Finance\Interfaces\SubscriptionServiceInterface;
use App\Modules\Finance\Interfaces\WalletRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletTransactionRepositoryInterface;
use App\Modules\Finance\Model\Enums\WalletTransactionType;
use Carbon\Carbon;

final class SubscriptionService extends BaseService implements SubscriptionServiceInterface
{
    public function __construct(
        private readonly SubscriptionPackageRepositoryInterface $packageRepository,
        private readonly DriverSubscriptionRepositoryInterface $subscriptionRepository,
        private readonly WalletRepositoryInterface             $walletRepository,
        private readonly WalletTransactionRepositoryInterface  $transactionRepository,
        private readonly FinanceRealtimeInterface              $realtimeService,
    ) {}

    /**
     * UC-46: Get available packages
     */
    public function getAvailablePackages(): ServiceReturn
    {
        return $this->execute(function (): array {
            $packages = $this->packageRepository->getActivePackages();
            
            return $packages->map(fn($pkg) => [
                'id'                            => (string) $pkg->id,
                'name'                          => $pkg->name,
                'description'                   => $pkg->description,
                'price'                         => (float) $pkg->price,
                'duration_days'                 => $pkg->duration_days,
                'service_fee_reduction_percent' => (float) $pkg->service_fee_reduction_percent,
            ])->toArray();
        });
    }

    /**
     * UC-46: Register subscription
     */
    public function registerSubscription(RegisterSubscriptionDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 1. Get and validate package
            $package = $this->packageRepository->find($dto->packageId);
            $this->validate($package !== null && $package->is_active, 'Gói thành viên không khả dụng.', 404);

            // 2. Check if driver already has active subscription
            $this->validate(
                !$this->subscriptionRepository->hasActiveSubscription($dto->userId),
                'Bạn đã có gói thành viên đang hoạt động.',
                400
            );

            // 3. Check wallet balance
            $wallet = $this->walletRepository->findByUserId($dto->userId);
            $this->validate($wallet !== null, 'Không tìm thấy ví.', 404);
            $this->validate($wallet->balance >= $package->price, 'Số dư ví không đủ để đăng ký gói này.', 400);

            // 4. Process Payment
            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore - (float) $package->price;

            $this->walletRepository->updateById($wallet->id, [
                'balance' => $balanceAfter,
            ]);

            // 5. Create Wallet Transaction (FEE)
            $this->transactionRepository->create([
                'wallet_id'      => $wallet->id,
                'type'           => WalletTransactionType::FEE,
                'amount'         => (float) $package->price,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'description'    => "Đăng ký gói thành viên: " . $package->name,
                'reference_type' => 'SubscriptionPackage',
                'reference_id'   => $package->id,
            ]);

            // 6. Activate Subscription
            $subscription = $this->subscriptionRepository->create([
                'driver_id'  => $dto->userId,
                'package_id' => $package->id,
                'started_at' => now(),
                'expires_at' => now()->addDays((int) $package->duration_days),
                'status'     => 'active',
                'price_paid' => (float) $package->price,
            ]);

            // 7. Broadcast Realtime
            $this->realtimeService->publishWalletEvent([
                'event'   => 'wallet.updated',
                'user_id' => (string) $dto->userId,
                'balance' => $balanceAfter,
                'type'    => 'subscription_payment',
            ]);

            $this->realtimeService->publish([
                'event'           => 'subscription.activated',
                'user_id'         => (string) $dto->userId,
                'subscription_id' => (string) $subscription->id,
                'package_name'    => $package->name,
                'expires_at'      => $subscription->expires_at->toIso8601String(),
            ]);

            return [
                'subscription_id' => (string) $subscription->id,
                'package_name'    => $package->name,
                'expires_at'      => $subscription->expires_at->toIso8601String(),
                'remaining_balance' => $balanceAfter,
            ];
        }, useTransaction: true);
    }
}
