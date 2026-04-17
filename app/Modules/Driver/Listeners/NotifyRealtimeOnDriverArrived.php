<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\DriverArrivedAtPickup;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener thông báo cho Khách hàng khi Tài xế bấm "Tôi đã đến".
 */
final class NotifyRealtimeOnDriverArrived
{
    public function __construct(
        private readonly DriverProfileRepositoryInterface $driverProfileRepository
    ) {
    }

    public function handle(DriverArrivedAtPickup $event): void
    {
        try {
            $driverProfile = $this->driverProfileRepository->findById($event->driverId);

            if (!$driverProfile) {
                return;
            }

            $payload = [
                'event'   => 'ride.driver_arrived',
                'ride_id' => (string) $event->rideId,
                'message' => 'Tài xế đã đến điểm đón.',
                'occurred_at' => now()->toIso8601String(),
            ];

            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.driver_arrived', [
                'ride_id'   => $event->rideId
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnDriverArrived failed', ['error' => $e->getMessage()]);
        }
    }
}
