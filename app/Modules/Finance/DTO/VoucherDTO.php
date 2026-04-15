<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Model\Voucher;

/**
 * DTO đại diện cho thông tin Voucher trả về cho API.
 */
final class VoucherDTO
{
    public function __construct(
        public readonly int    $id,
        public readonly string $code,
        public readonly string $serviceType,
        public readonly string $discountType,
        public readonly float  $discountValue,
        public readonly float  $minOrderAmount,
        public readonly ?float $maxDiscountValue,
        public readonly string $validUntil,
        public readonly string $description,
        public readonly bool   $isSaved,
        public readonly string $status, // Available, Expired, NotEligible
    ) {
    }

    /**
     * Factory method để tạo DTO từ Model.
     */
    public static function fromModel(Voucher $voucher, bool $isSaved = false): self
    {
        $status = 'Available'; // Mặc định là Available
        if ($voucher->isExpired()) {
            $status = 'Expired'; // Nếu hết hạn thì là Expired
        }

        return new self(
            id:                $voucher->id,
            code:              $voucher->code,
            serviceType:       $voucher->service_type->getLabel(),
            discountType:      $voucher->discount_type->getLabel(),
            discountValue:     $voucher->discount_value,
            minOrderAmount:    $voucher->min_order_amount,
            maxDiscountValue:  $voucher->max_discount_amount,
            validUntil:        $voucher->valid_until->toDateTimeString(),
            description:       $voucher->description ?? '',
            isSaved:           $isSaved,
            status:            $status,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'code'               => $this->code,
            'service_type'       => $this->serviceType,
            'discount_type'      => $this->discountType,
            'discount_value'     => $this->discountValue,
            'min_order_amount'   => $this->minOrderAmount,
            'max_discount_value' => $this->maxDiscountValue,
            'valid_until'        => $this->validUntil,
            'description'        => $this->description,
            'is_saved'           => $this->isSaved,
            'status'             => $this->status,
        ];
    }
}
