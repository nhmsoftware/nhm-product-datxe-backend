<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class WalletTransactionDetailDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $transactionId,
    ) {}

    public static function fromRequest(Request $request, string $transactionId): self
    {
        return new self(
            userId: (string) $request->user()->id,
            transactionId: $transactionId,
        );
    }
}
