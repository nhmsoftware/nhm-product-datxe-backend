<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PenaltyRuleCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $ruleId,
        public readonly string $ruleName,
        public readonly int $violationType,
        public readonly int $applicableRole
    ) {}
}
