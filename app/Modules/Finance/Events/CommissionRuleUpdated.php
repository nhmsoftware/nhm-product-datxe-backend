<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Model\CommissionRule;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CommissionRuleUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $ruleId,
        public readonly int    $targetType,
        public readonly int    $serviceType,
        public readonly float  $oldRate,
        public readonly float  $newRate,
        public readonly ?string $adminId = null,
    ) {}
}
