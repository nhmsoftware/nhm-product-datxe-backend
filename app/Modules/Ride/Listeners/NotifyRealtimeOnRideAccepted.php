<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\RideAcceptedByDriver;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use App\Modules\Ride\Services\VehicleTypeCatalogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnRideAccepted implements ShouldQueue
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RideRepositoryInterface $rideRepository,
        private readonly VehicleTypeCatalogService $vehicleTypeCatalogService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(RideAcceptedByDriver $event): void
    {
        try {
            $driver = $this->userRepository->findDriverWithProfileById($event->driverId);
            $ride = $this->rideRepository->find($event->rideId);

            if (!$driver || !$driver->driverProfile || !$ride) {
                Log::warning('Cannot notify realtime ride.accepted: Missing data', [
                    'ride_id' => $event->rideId,
                    'driver_id' => $event->driverId
                ]);
                return;
            }

            $payload = [
                'event'       => 'ride.accepted',
                'ride_id'     => (string) $event->rideId,
                'customer_id' => (string) $event->customerId,
                'driver'      => [
                    'id'             => (string) $driver->id,
                    'full_name'      => $driver->driverProfile->full_name,
                    'phone'          => $driver->phone,
                    'avatar_url'     => $driver->driverProfile->avatar_url ?? null,
                    'vehicle_name'   => $driver->driverProfile->vehicle_name ?? null,
                    'vehicle_number' => $driver->driverProfile->vehicle_plate ?? null,
                    'vehicle_type_id'   => $driver->driverProfile->vehicle_type,
                    'vehicle_type_label' => $driver->driverProfile->vehicle_type !== null
                        ? $this->vehicleTypeCatalogService->getLabelById((int) $driver->driverProfile->vehicle_type)
                        : null,
                    'current_lat'    => (float) $driver->driverProfile->current_lat,
                    'current_lng'    => (float) $driver->driverProfile->current_lng,
                ],
                'message'     => "Tài xế {$driver->driverProfile->full_name} đã nhận chuyến và đang di chuyển đến bạn.",
                'occurred_at' => now()->toIso8601String(),
            ];

            // Publish to Redis
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: ride.accepted', [
                'ride_id' => $event->rideId,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideAccepted failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
