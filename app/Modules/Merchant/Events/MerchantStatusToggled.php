<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MerchantStatusToggled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $merchantProfileId,
        public readonly bool   $isOpen,
        public readonly int    $activeOrdersCount = 0,
        public readonly string $toggledAt = '',
    ) {}
}
