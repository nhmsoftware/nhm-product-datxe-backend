<?php

declare(strict_types=1);

namespace App\Modules\Finance\Interfaces;

interface FinanceRealtimeInterface
{
    /**
     * Publish a message to the realtime channel
     */
    public function publish(array $payload): void;

    /**
     * Publish wallet specific event
     */
    public function publishWalletEvent(array $payload): void;
}
