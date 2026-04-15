<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

interface RideCommunicationRealtimeInterface
{
    /**
     * Publish event chat/call sang Node realtime service (UC-14).
     *
     * @param array<string, mixed> $payload
     */
    public function publish(array $payload): void;
}
