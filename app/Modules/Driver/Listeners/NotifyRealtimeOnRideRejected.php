<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RideRejected;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý thông báo realtime khi tài xế từ chối chuyến xe.
 * Gửi tín hiệu qua Redis channel 'ride.communication.events'.
 */
final class NotifyRealtimeOnRideRejected
{
    public function __construct(
        private readonly DriverProfileRepositoryInterface $driverProfileRepository
    ) {}

    public function handle(RideRejected $event): void
    {
        try {
            $driverProfile = $this->driverProfileRepository->findById($event->driverId);

            $payload = [
                'event'   => 'ride.rejected',
                'ride_id' => (string) $event->rideId,
                'driver'  => [
                    'id'        => (string) $event->driverId,
                    'full_name' => $driverProfile?->full_name ?? 'Driver',
                ],
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by nhm-realtime service
            Redis::connection('default')->publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.rejected', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideRejected failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
