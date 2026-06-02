<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

/**
 * UC-45 A4: Driver hủy giao dịch nạp tiền đang Pending.
 */
final class CancelTopUpDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $topUpId,
    ) {}

    public static function fromRequest(Request $request, string $topUpId): self
    {
        return new self(
            userId:  (string) $request->user()->id,
            topUpId: $topUpId,
        );
    }
}
