<?php

declare(strict_types=1);

namespace App\Modules\Driver\Listeners;

use App\Modules\Driver\Events\RideAccepted;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRideAccepted implements ShouldQueue
{
    public function __construct(
        private readonly DriverProfileRepositoryInterface $driverProfileRepository
    ) {}

    public function handle(RideAccepted $event): void
    {
        try {
            $driverProfile = $this->driverProfileRepository->findById($event->driverId);

            if (!$driverProfile) {
                Log::warning('NotifyRealtimeOnRideAccepted skipped: Driver profile not found', [
                    'driver_id' => $event->driverId,
                    'ride_id'   => $event->rideId
                ]);
                return;
            }

            $payload = [
                'event'   => 'ride.accepted',
                'ride_id' => (string) $event->rideId,
                'driver'  => [
                    'id'             => (string) $driverProfile->id,
                    'full_name'      => $driverProfile->full_name,
                    'vehicle_name'   => $driverProfile->vehicle_name,
                    'vehicle_number' => $driverProfile->vehicle_number,
                    'vehicle_type'   => $driverProfile->vehicle_type,
                    'current_lat'    => (float) $driverProfile->current_lat,
                    'current_lng'    => (float) $driverProfile->current_lng,
                ],
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by nhm-realtime service
            Redis::publish('ride.communication.events', json_encode($payload));

            Log::info('Realtime notification sent: ride.accepted', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideAccepted failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
