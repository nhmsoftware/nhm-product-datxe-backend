<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RidePickedUp;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Listener xử lý việc thông báo Realtime cho Khách hàng khi Tài xế đã đón khách thành công.
 */
final class NotifyRealtimeOnRidePickedUp implements ShouldQueue
{
    public function __construct(
        private readonly DriverProfileRepositoryInterface $driverProfileRepository
    ) {
    }

    public function handle(RidePickedUp $event): void
    {
        try {
            $driverProfile = $this->driverProfileRepository->findById($event->driverId);

            if (!$driverProfile) {
                Log::warning('NotifyRealtimeOnRidePickedUp skipped: Driver profile not found', [
                    'driver_id' => $event->driverId,
                    'ride_id'   => $event->rideId
                ]);
                return;
            }

            $payload = [
                'event'   => 'ride.picked_up',
                'ride_id' => (string) $event->rideId,
                'driver'  => [
                    'id'             => (string) $driverProfile->id,
                    'full_name'      => $driverProfile->full_name,
                    'current_lat'    => (float) $driverProfile->current_lat,
                    'current_lng'    => (float) $driverProfile->current_lng,
                ],
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish vào Redis channel mà nhm-realtime service đang nghe
            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.picked_up', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRidePickedUp failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
