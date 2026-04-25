<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class InitiateTopUpDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly float $amount,
        public readonly string $paymentMethod,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) $request->user()->id,
            amount: (float) $request->input('amount'),
            paymentMethod: (string) $request->input('payment_method'),
        );
    }
}
