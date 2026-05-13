<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class AdminDriverFinanceSummaryDTO
{
    public function __construct(
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            startDate: $request->query('start_date'),
            endDate: $request->query('end_date'),
        );
    }
}
