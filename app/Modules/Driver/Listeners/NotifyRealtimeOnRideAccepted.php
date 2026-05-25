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
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: ride.accepted', [
                'ride_id'   => $event->rideId,
                'driver_id' => $event->driverId
            ]);

            // Notify merchant if this ride belongs to a FoodOrder
            $foodOrder = \App\Modules\Food\Model\FoodOrder::with('merchant')->where('ride_id', $event->rideId)->first();
            if ($foodOrder && $foodOrder->merchant) {
                $merchantPayload = [
                    'event'       => 'food_order.driver_assigned',
                    'order_id'    => (string) $foodOrder->id,
                    'ride_id'     => (string) $event->rideId,
                    'user_id'     => (string) $foodOrder->merchant->user_id, // Gửi cho chủ nhà hàng
                    'driver_id'   => (string) $event->driverId,
                    'message'     => 'Tài xế đã nhận đơn và đang trên đường tới.',
                    'occurred_at' => now()->toIso8601String(),
                ];
                Redis::publish($channel, json_encode($merchantPayload));

                Log::info('Realtime notification sent to merchant: food_order.driver_assigned', [
                    'order_id' => $foodOrder->id,
                    'merchant_user_id' => $foodOrder->merchant->user_id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnRideAccepted failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId
            ]);
        }
    }
}
