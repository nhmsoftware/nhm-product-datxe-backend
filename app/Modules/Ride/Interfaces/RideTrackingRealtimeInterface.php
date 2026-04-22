<?php

declare(strict_types=1);

namespace App\Modules\Ride\Interfaces;

interface RideTrackingRealtimeInterface
{
    /**
     * Publish tracking event sang Redis/Node.js Realtime.
     *
     * @param array<string, mixed> $payload
     */
    public function publish(array $payload): void;
}
