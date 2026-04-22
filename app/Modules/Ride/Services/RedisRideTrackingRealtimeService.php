<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface;
use Illuminate\Contracts\Redis\Factory;

/**
 * Service xử lý việc publish các sự kiện tracking/lifecycle của chuyến xe sang Redis (Node.js Realtime).
 */
final class RedisRideTrackingRealtimeService implements RideTrackingRealtimeInterface
{
    public function __construct(
        private readonly Factory $redis
    ) {
    }

    /**
     * Publish payload sang Redis channel `ride.tracking.events`.
     *
     * @param array<string, mixed> $payload
     */
    public function publish(array $payload): void
    {
        $this->redis->connection()->publish(
            'ride.tracking.events',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }
}
