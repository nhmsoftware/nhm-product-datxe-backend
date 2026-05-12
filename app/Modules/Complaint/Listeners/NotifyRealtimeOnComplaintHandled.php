<?php

declare(strict_types=1);

namespace App\Modules\Complaint\Listeners;

use App\Modules\Complaint\Events\ComplaintHandled;
use App\Modules\Complaint\Interfaces\ComplaintRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

final class NotifyRealtimeOnComplaintHandled implements ShouldQueue
{
    public function __construct(
        private readonly ComplaintRepositoryInterface $complaintRepository,
    ) {}

    public function handle(ComplaintHandled $event): void
    {
        try {
            $complaint = $this->complaintRepository->find($event->complaintId);
            if (!$complaint) return;

            $payload = [
                'event' => 'complaint.handled',
                'user_id' => (string) $complaint->sender_id, // Complainant
                'complaint_id' => (string) $event->complaintId,
                'action' => $event->action,
                'message' => 'Xử lý khiếu nại của bạn: ' . $event->action,
                'occurred_at' => $event->processedAt,
            ];

            $channel = env('REDIS_COMMUNICATION_CHANNEL', 'ride.communication.events');
            Redis::publish($channel, json_encode($payload));

            Log::info('Realtime notification sent: complaint.handled', [
                'complaint_id' => $event->complaintId,
            ]);

        } catch (\Exception $e) {
            Log::error('NotifyRealtimeOnComplaintHandled failed', [
                'error' => $e->getMessage(),
                'complaint_id' => $event->complaintId
            ]);
        }
    }
}
