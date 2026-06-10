<?php

declare(strict_types=1);

namespace App\Modules\Operation\Services;

use App\Core\Services\BaseService;
use App\Modules\Operation\Interfaces\DispatchServiceInterface;
use App\Modules\Operation\Interfaces\LocationRepositoryInterface;
use App\Modules\Operation\Jobs\PriorityDispatchFallbackJob;
use App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface;
use App\Modules\Pricing\Model\Enums\ScheduledDispatchMode;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Services\VehicleTypeCatalogService;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\Finance\Interfaces\CreditWalletConfigRepositoryInterface;
use App\Modules\Finance\Interfaces\WalletRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class DispatchService extends BaseService implements DispatchServiceInterface
{
    private const ROUND_1_RADIUS_KM = 2.0;
    private const ROUND_2_RADIUS_KM = 3.0;
    private const FALLBACK_DELAY_SECONDS = 60;

    public function __construct(
        private readonly RideRepositoryInterface $rideRepository,
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly CreditWalletConfigRepositoryInterface $walletConfigRepository,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly PricingGlobalSettingRepositoryInterface $pricingGlobalSettingRepository,
        private readonly VehicleTypeCatalogService $vehicleTypeCatalogService,
    ) {
    }

    /**
     * @inheritDoc
     * Phát đơn giao hàng cho tài xế theo cơ chế ưu tiên vòng 1 (2km) chỉ nội bộ, vòng 2 (3km) mới có đối tác.
     */
    public function initiateDispatch(string $rideId): void
    {
        $this->execute(function () use ($rideId) {
            $ride = $this->rideRepository->find($rideId);
            if (!$ride || $ride->status !== RideStatus::PENDING) {
                return;
            }

            $isOpenMode = $this->isOpenPoolMode();
            $modeLabel  = $isOpenMode ? 'CONG KHAI (Tat ca tai xe)' : 'UU TIEN NOI BO (Doi xe nha)';

            // 1. Tim cac driver trong ban kinh 2km
            $nearbyDriverIds = $this->locationRepository->findNearbyDriverIds(
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng,
                self::ROUND_1_RADIUS_KM
            );

            // 2. Loc tai xe theo che do phat song
            //    - OPEN_POOL  : groupType = null → bat len tat ca tai xe du dieu kien (ca noi bo + doi tac)
            //    - INTERNAL_PRIORITY: groupType = INTERNAL → chi bat len doi xe nha
            $groupType = $isOpenMode ? null : DriverGroupType::INTERNAL->value;

            $eligibleDrivers = $this->driverProfileRepository->findEligibleDrivers(
                userIds: $nearbyDriverIds,
                vehicleType: (int) $ride->vehicle_type,
                groupType: $groupType
            );

            // 3. Thong bao cho cac tai xe (loai tru nhung nguoi da tu choi don nay)
            $notifiedCount = 0;
            foreach ($eligibleDrivers as $driver) {
                if (!$this->rideRepository->isRejectedByDriver($rideId, $driver->user_id)) {
                    $this->notifyDriverOfNewRide($driver->user_id, $ride);
                    $notifiedCount++;
                }
            }

            // 4. Len lich vong 2 (Fallback) sau 60s
            PriorityDispatchFallbackJob::dispatch($rideId)
                ->delay(now()->addSeconds(self::FALLBACK_DELAY_SECONDS));

            Log::info("PriorityDispatch [Vong 1]: Bat dau phat song cho chuyen {$rideId}", [
                'che_do'           => $modeLabel,
                'loai_xe'          => $this->vehicleTypeCatalogService->getCodeById((int) $ride->vehicle_type),
                'ban_kinh_km'      => self::ROUND_1_RADIUS_KM,
                'tai_xe_gan_do'    => count($nearbyDriverIds),
                'tai_xe_du_dieu_kien' => $eligibleDrivers->count(),
                'da_gui_thong_bao' => $notifiedCount,
            ]);
        });
    }

    /**
     * @inheritDoc
     */
    public function fallbackToPartnerDrivers(string $rideId): void
    {
        $this->execute(function () use ($rideId) {
            $ride = $this->rideRepository->find($rideId);

            // Kiem tra trang thai: Neu da co nguoi nhan hoac bi huy thi thoi
            if (!$ride || $ride->status !== RideStatus::PENDING) {
                Log::info("PriorityDispatch [Vong 2 - Bo qua]: Chuyen {$rideId} khong con o trang thai cho tai xe", [
                    'trang_thai' => $ride->status->name ?? 'Da xoa',
                ]);
                return;
            }

            $isOpenMode = $this->isOpenPoolMode();
            $modeLabel  = $isOpenMode ? 'CONG KHAI (Tat ca tai xe)' : 'UU TIEN NOI BO (Chi doi tac)';

            // 1. Mo rong ban kinh quet (3km)
            //    - OPEN_POOL  : groupType = null → bat len tat ca tai xe (ca noi bo + doi tac)
            //    - INTERNAL_PRIORITY: groupType = PARTNER → chi bat len tai xe doi tac
            $nearbyDriverIds = $this->locationRepository->findNearbyDriverIds(
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng,
                self::ROUND_2_RADIUS_KM
            );

            $groupType = $isOpenMode ? null : DriverGroupType::PARTNER->value;

            $eligibleDrivers = $this->driverProfileRepository->findEligibleDrivers(
                userIds: $nearbyDriverIds,
                vehicleType: (int) $ride->vehicle_type,
                groupType: $groupType
            );

            // UC-117: Kiem tra Credit Wallet cho Partner drivers
            $config = $this->walletConfigRepository->getLatestConfig();
            if ($config->auto_lock) {
                $blockedUserIds  = $this->walletRepository->getLowBalanceUserIds($config->min_balance);
                $beforeFilter    = $eligibleDrivers->count();
                $eligibleDrivers = $eligibleDrivers->filter(function ($driver) use ($blockedUserIds) {
                    return !in_array($driver->user_id, $blockedUserIds);
                });
                $blockedCount = $beforeFilter - $eligibleDrivers->count();

                if ($blockedCount > 0) {
                    Log::info("PriorityDispatch [Vong 2]: Khoa {$blockedCount} tai xe do so du Credit Wallet thap", [
                        'chuyen_id'      => $rideId,
                        'so_bi_khoa'     => $blockedCount,
                        'nguong_toi_thieu' => $config->min_balance,
                    ]);
                }
            }

            // 2. Thong bao (loai tru nhung nguoi da tu choi don nay)
            $notifiedCount = 0;
            foreach ($eligibleDrivers as $driver) {
                if (!$this->rideRepository->isRejectedByDriver($rideId, $driver->user_id)) {
                    $this->notifyDriverOfNewRide($driver->user_id, $ride);
                    $notifiedCount++;
                }
            }

            Log::info("PriorityDispatch [Vong 2 - Du phong]: Hoan tat phat song cho chuyen {$rideId}", [
                'che_do'              => $modeLabel,
                'loai_xe'             => $this->vehicleTypeCatalogService->getCodeById((int) $ride->vehicle_type),
                'ban_kinh_km'         => self::ROUND_2_RADIUS_KM,
                'tai_xe_gan_do'       => count($nearbyDriverIds),
                'tai_xe_du_dieu_kien' => $eligibleDrivers->count(),
                'da_gui_thong_bao'    => $notifiedCount,
            ]);

            if ($notifiedCount === 0) {
                Log::warning("PriorityDispatch [Vong 2]: Khong co tai xe nao nhan duoc thong bao cho chuyen {$rideId}", [
                    'che_do'           => $modeLabel,
                    'tai_xe_gan_do'    => count($nearbyDriverIds),
                    'tai_xe_du_dieu_kien' => $eligibleDrivers->count(),
                    'goi_y'            => count($nearbyDriverIds) > 0 && $eligibleDrivers->count() === 0
                        ? 'Tai xe o gan nhung khong du dieu kien: kiem tra trang thai online, loai xe, hoac trang thai ACTIVE/COOLDOWN'
                        : 'Khong co tai xe nao trong ban kinh ' . self::ROUND_2_RADIUS_KM . 'km',
                ]);
            }
        });
    }

    /**
     * Kiem tra xem he thong dang o che do OPEN_POOL (cong khai) hay khong.
     *
     * - OPEN_POOL         : Tat ca tai xe du dieu kien deu nhan duoc thong bao (khong loc theo nhom).
     * - INTERNAL_PRIORITY : Vong 1 chi bat len Doi xe nha, Vong 2 chi bat len Tai xe doi tac.
     * - Khong co cau hinh : Mac dinh OPEN_POOL.
     */
    private function isOpenPoolMode(): bool
    {
        $settings = $this->pricingGlobalSettingRepository->getSettings();

        if (!$settings || $settings->scheduled_dispatch_mode === null) {
            return true; // Mac dinh: Cong khai
        }

        return $settings->scheduled_dispatch_mode === ScheduledDispatchMode::OPEN_POOL;
    }

    /**
     * Gui event New Ride Offer toi Driver qua Redis Pub/Sub
     */
    private function notifyDriverOfNewRide(string $userId, $ride): void
    {
        $payload = [
            'user_id'              => (string) $userId,
            'event'                => 'ride.new_offer',
            'ride_id'              => (string) $ride->id,
            'ride_type'            => $ride->ride_type->name,
            'travel_date'          => $ride->travel_date,
            'travel_time'          => $ride->travel_time,
            'pickup_address'       => $ride->pickup_address,
            'destination_address'  => $ride->destination_address,
            'distance_km'          => round($ride->distance / 1000, 2),
            'total_price'          => (float) $ride->total_price,
            'vehicle_type_id'      => (int) $ride->vehicle_type,
            'vehicle_type'         => $this->vehicleTypeCatalogService->getCodeById((int) $ride->vehicle_type),
            'occurred_at'          => now()->toIso8601String(),
        ];

        // Channel name matching realtime/.env REDIS_COMMUNICATION_CHANNEL
        $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
        Redis::publish($channel, json_encode($payload));
    }
}
