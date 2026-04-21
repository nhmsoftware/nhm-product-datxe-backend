<?php

declare(strict_types=1);

namespace App\Modules\Communication\Listeners;

use App\Modules\Communication\Events\ChatMessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnChatMessageSent implements ShouldQueue
{
    public function handle(ChatMessageSent $event): void
    {
        try {
            $message = $event->message;

            $payload = [
                'event'       => 'ride.chat_message',
                'ride_id'     => (string) $message->ride_id,
                'user_id'     => (string) $message->receiver_id, // Gửi tới receiver's userRoom
                'sender_id'   => (string) $message->sender_id,
                'message'     => $message->message,
                'occurred_at' => $message->created_at?->toIso8601String() ?? now()->toIso8601String(),
            ];

            // Publish to Redis channel expected by realtime service
            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime chat message sent', [
                'ride_id'     => $message->ride_id,
                'sender_id'   => $message->sender_id,
                'receiver_id' => $message->receiver_id
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnChatMessageSent failed', [
                'error'   => $e->getMessage(),
                'ride_id' => $event->message->ride_id
            ]);
        }
    }
}
