<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\ApplyVoucherQuickRequest;

/**
 * DTO cho hành động áp dụng voucher nhanh (UC-22).
 */
final readonly class ApplyVoucherQuickDTO
{
    public function __construct(
        public int $customerId,
        public int $voucherId
    ) {
    }

    /**
     * Factory method tạo DTO từ FormRequest.
     */
    public static function fromRequest(ApplyVoucherQuickRequest $request): self
    {
        return new self(
            customerId: (int) $request->user()->id,
            voucherId: (int) $request->route('id')
        );
    }
}
