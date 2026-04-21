<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\ApplyVoucherRequest;

/**
 * DTO cho request áp dụng / xóa voucher vào chuyến đi (UC-11).
 */
final class ApplyVoucherDTO
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $rideId,
        public readonly string $voucherCode,
    ) {
    }

    /**
     * Khởi tạo DTO từ FormRequest đã validate.
     * rideId lấy từ route parameter, customerId từ authenticated user.
     */
    public static function fromRequest(ApplyVoucherRequest $request): self
    {
        return new self(
            customerId:   (string) $request->user()->id,
            rideId:       (string) $request->input('rideId'),
            voucherCode:  $request->string('voucher_code')->toString(),
        );
    }
}
