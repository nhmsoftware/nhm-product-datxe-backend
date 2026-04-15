<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Modules\Ride\Interfaces\RideCommunicationRealtimeInterface;
use Illuminate\Contracts\Redis\Factory;

final class RedisRideCommunicationRealtimeService implements RideCommunicationRealtimeInterface
{
    public function __construct(
        private readonly Factory $redis
    ) {
    }

    public function publish(array $payload): void
    {
        $this->redis->connection()->publish(
            'ride.communication.events',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }
}
