<?php

declare(strict_types=1);

namespace App\Modules\Operation\Services;

use App\Core\Services\BaseService;
use App\Modules\Operation\Interfaces\DispatchServiceInterface;
use App\Modules\Operation\Interfaces\LocationRepositoryInterface;
use App\Modules\Operation\Jobs\PriorityDispatchFallbackJob;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\Enums\DriverGroupType;
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
        private readonly DriverProfileRepositoryInterface $driverProfileRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function initiateDispatch(string $rideId): void
    {
        $this->execute(function () use ($rideId) {
            $ride = $this->rideRepository->find($rideId);
            if (!$ride || $ride->status !== RideStatus::PENDING) {
                return;
            }

            // 1. Tìm các driver trong bán kính 2km
            $nearbyDriverIds = $this->locationRepository->findNearbyDriverIds(
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng,
                self::ROUND_1_RADIUS_KM
            );

            // 2. Lọc nhóm "Đội xe nhà" (INTERNAL)
            $eligibleDrivers = $this->driverProfileRepository->findEligibleDrivers(
                userIds: $nearbyDriverIds,
                vehicleType: (int) $ride->vehicle_type->value,
                groupType: DriverGroupType::INTERNAL->value
            );

            // 3. Thông báo cho các tài xế (loại trừ những người đã từ chối đơn này)
            $notifiedCount = 0;
            foreach ($eligibleDrivers as $driver) {
                if (!$this->rideRepository->isRejectedByDriver($rideId, $driver->user_id)) {
                    $this->notifyDriverOfNewRide($driver->user_id, $ride);
                    $notifiedCount++;
                }
            }

            // 4. Lên lịch vòng 2 (Fallback) sau 60s
            PriorityDispatchFallbackJob::dispatch($rideId)
                ->delay(now()->addSeconds(self::FALLBACK_DELAY_SECONDS));

            Log::info("PriorityDispatch: Round 1 initiated for Ride {$rideId}", [
                'nearby_count' => count($nearbyDriverIds),
                'eligible_count' => $eligibleDrivers->count(),
                'notified_count' => $notifiedCount
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
            
            // Check status: Nếu đã có người nhận hoặc bị hủy thì thôi
            if (!$ride || $ride->status !== RideStatus::PENDING) {
                Log::info("PriorityDispatch: Fallback skipped for Ride {$rideId} (Status: " . ($ride->status->name ?? 'Deleted') . ")");
                return;
            }

            // 1. Mở rộng bán kính quét (3km) cho toàn bộ "Tài xế đối tác" (PARTNER)
            // Lưu ý: Có thể thông báo cho cả Internal nếu muốn, nhưng yêu cầu ghi "Phát sóng cho toàn bộ Tài xế đối tác"
            $nearbyDriverIds = $this->locationRepository->findNearbyDriverIds(
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng,
                self::ROUND_2_RADIUS_KM
            );

            $eligibleDrivers = $this->driverProfileRepository->findEligibleDrivers(
                userIds: $nearbyDriverIds,
                vehicleType: (int) $ride->vehicle_type->value,
                groupType: DriverGroupType::PARTNER->value // Chỉ lấy đối tác ở vòng 2
            );

            // 2. Thông báo (loại trừ những người đã từ chối đơn này)
            $notifiedCount = 0;
            foreach ($eligibleDrivers as $driver) {
                if (!$this->rideRepository->isRejectedByDriver($rideId, $driver->user_id)) {
                    $this->notifyDriverOfNewRide($driver->user_id, $ride);
                    $notifiedCount++;
                }
            }

            Log::info("PriorityDispatch: Round 2 (Fallback) executed for Ride {$rideId}", [
                'nearby_count' => count($nearbyDriverIds),
                'eligible_count' => $eligibleDrivers->count(),
                'notified_count' => $notifiedCount
            ]);
        });
    }

    /**
     * Gửi event New Ride Offer tới Driver qua Redis Pub/Sub
     */
    private function notifyDriverOfNewRide(string $userId, $ride): void
    {
        $payload = [
            'user_id' => $userId,
            'event' => 'ride.new_offer',
            'ride_id' => $ride->id,
            'ride_type' => $ride->ride_type->name,
            'travel_date' => $ride->travel_date,
            'travel_time' => $ride->travel_time,
            'pickup_address' => $ride->pickup_address,
            'destination_address' => $ride->destination_address,
            'distance_km' => round($ride->distance / 1000, 2),
            'total_price' => (float) $ride->total_price,
            'vehicle_type' => $ride->vehicle_type->name,
            'occurred_at' => now()->toIso8601String(),
        ];

        // Channel name matching realtime/.env REDIS_COMMUNICATION_CHANNEL
        $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
        Redis::publish($channel, json_encode($payload));
    }
}
