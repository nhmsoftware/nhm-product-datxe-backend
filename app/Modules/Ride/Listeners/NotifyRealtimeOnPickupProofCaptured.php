<?php

declare(strict_types=1);

namespace App\Modules\Ride\Listeners;

use App\Modules\Ride\Events\PickupProofCaptured;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * UC-37: Listener relay sự kiện PickupProofCaptured lên Redis → Node.js → Customer/Merchant.
 */
final class NotifyRealtimeOnPickupProofCaptured implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PickupProofCaptured $event): void
    {
        try {
            $payload = [
                'event'       => 'ride.pickup_proof_captured',
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
                    ? 'Tài xế đã lấy hàng và tải ảnh xác nhận.'
                    : 'Tài xế đã xác nhận lấy hàng (thủ công).',
                'occurred_at' => $event->capturedAt,
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: ride.pickup_proof_captured', [
                'ride_id'    => $event->rideId,
                'has_photo'  => $event->photoUrl !== null,
                'skip_reason'=> $event->skipReason,
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnPickupProofCaptured failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->rideId,
            ]);
        }
    }
}
