<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class UpdateCreditWalletConfigDTO
{
    public function __construct(
        public readonly float $minBalance,
        public readonly bool $autoLock,
        public readonly ?string $commissionRule,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            minBalance: (float) $request->input('min_balance'),
            autoLock: (bool) $request->input('auto_lock', true),
            commissionRule: $request->input('commission_rule'),
        );
    }
}
