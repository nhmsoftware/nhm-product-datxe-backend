<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\DriverArrivedAtPickup;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener thông báo cho Khách hàng khi Tài xế bấm "Tôi đã đến".
 */
final class NotifyRealtimeOnDriverArrived implements ShouldQueue
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
                Log::warning('NotifyRealtimeOnDriverArrived skipped: Driver profile not found', [
                    'driver_id' => $event->driverId,
                    'ride_id'   => $event->rideId
                ]);
                return;
            }

            $payload = [
                'event'   => 'ride.arrived',
                'ride_id' => (string) $event->rideId,
                'driver'  => [
                    'id'          => (string) $driverProfile->id,
                    'current_lat' => (float) $driverProfile->current_lat,
                    'current_lng' => (float) $driverProfile->current_lng,
                ],
                'message' => 'Tài xế đã đến điểm đón.',
                'occurred_at' => now()->toIso8601String(),
            ];

            // Emit to Node.js WebSocket via Redis
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: ride.driver_arrived', [
                'ride_id'   => $event->rideId
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnDriverArrived failed', ['error' => $e->getMessage()]);
        }
    }
}
