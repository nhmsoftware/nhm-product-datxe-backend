<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $refundId,
        public readonly string $adminId,
        public readonly string $status,
        public readonly ?float $amount = null,
        public readonly string $processedAt,
    ) {}
}
