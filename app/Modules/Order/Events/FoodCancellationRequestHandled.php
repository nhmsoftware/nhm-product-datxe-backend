<?php

declare(strict_types=1);

namespace App\Modules\Order\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FoodCancellationRequestHandled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $orderId,
        public string $customerId,
        public string $action, // accepted, rejected
        public ?string $reason = null
    ) {}
}
