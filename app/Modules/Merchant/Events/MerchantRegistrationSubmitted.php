<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MerchantRegistrationSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $applicationId,
        public readonly string $storeName,
        public readonly string $submittedAt = '',
    ) {
        // can set submittedAt to now if empty
    }
}
