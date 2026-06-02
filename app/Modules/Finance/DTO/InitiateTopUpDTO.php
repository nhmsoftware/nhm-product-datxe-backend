<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

/**
 * UC-45: Tạo yêu cầu nạp tiền.
 * payment_method_code: code của phương thức từ bảng payment_methods (momo, zalopay, bank_transfer...)
 */
final class InitiateTopUpDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly float  $amount,
        public readonly string $paymentMethodCode,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId:            (string) $request->user()->id,
            amount:            (float)  $request->input('amount'),
            paymentMethodCode: (string) $request->input('payment_method_code'),
        );
    }
}

