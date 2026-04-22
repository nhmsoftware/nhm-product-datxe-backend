<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class WalletTransactionDetailDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $transactionId,
    ) {}

    public static function fromRequest(Request $request, int $transactionId): self
    {
        return new self(
            userId: (int) $request->user()->id,
            transactionId: $transactionId,
        );
    }
}
