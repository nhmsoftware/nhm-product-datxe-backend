<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MerchantCommissionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $merchantProfileId,
        public readonly string $packageKey,
        public readonly float  $newRate,
        public readonly string $updatedAt = '',
    ) {}
}
