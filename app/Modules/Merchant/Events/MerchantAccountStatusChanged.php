<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

final class MerchantAccountStatusChanged
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $userId,
        public readonly bool   $isLocked,
        public readonly ?string $reason = null,
        public readonly ?\DateTimeInterface $expiredAt = null,
    ) {}
}
