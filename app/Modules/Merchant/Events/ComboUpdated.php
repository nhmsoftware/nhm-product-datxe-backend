<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ComboUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $comboId,
        public readonly string $merchantProfileId
    ) {}
}
