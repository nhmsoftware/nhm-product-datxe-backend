<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Interfaces\FinanceRealtimeInterface;
use Illuminate\Contracts\Redis\Factory;

final class RedisFinanceRealtimeService implements FinanceRealtimeInterface
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

    public function publishWalletEvent(array $payload): void
    {
        $this->redis->connection()->publish(
            'finance.events',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }
}
