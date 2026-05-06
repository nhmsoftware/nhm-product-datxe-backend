<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VoucherUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $voucherId,
    ) {
    }
}
