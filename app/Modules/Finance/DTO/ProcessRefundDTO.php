<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\ProcessRefundRequest;
use App\Modules\Finance\Model\Enums\RefundStatus;

final class ProcessRefundDTO
{
    public function __construct(
        public readonly string $refundId,
        public readonly string $adminId,
        public readonly RefundStatus $status,
        public readonly ?float $amount = null,
        public readonly ?string $note = null,
    ) {}

    public static function fromRequest(ProcessRefundRequest $request, string $id): self
    {
        return new self(
            refundId: $id,
            adminId: (string) $request->user()->id,
            status: RefundStatus::from($request->input('status')),
            amount: $request->input('amount') ? (float) $request->input('amount') : null,
            note: $request->input('note'),
        );
    }
}
