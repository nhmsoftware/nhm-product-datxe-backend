<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\DeliveryProofCaptured;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * UC-38: Listener relay sự kiện DeliveryProofCaptured lên Redis.
 */
final class NotifyRealtimeOnDeliveryProofCaptured implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DeliveryProofCaptured $event): void
    {
        try {
            $payload = [
                'event'       => 'ride.delivery_proof_captured',
                'ride_id'     => (string) $event->rideId,
                'driver_id'   => (string) $event->driverId,
                'customer_id' => (string) $event->customerId,
                'photo_url'   => $event->photoUrl,
                'is_skipped'  => $event->photoUrl === null,
                'skip_reason' => $event->skipReason,
                'location'    => [
                    'lat' => $event->capturedLat,
                    'lng' => $event->capturedLng,
                ],
                'message'     => $event->photoUrl
                    ? 'Đơn hàng của bạn đã được giao thành công kèm ảnh xác nhận.'
                    : 'Đơn hàng của bạn đã được giao thành công.',
                'occurred_at' => $event->capturedAt,
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnDeliveryProofCaptured failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId,
            ]);
        }
    }
}
