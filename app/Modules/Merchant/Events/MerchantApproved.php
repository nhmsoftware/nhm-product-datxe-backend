<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final class MerchantApproved
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $userId,
    ) {}
}
