<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Model\Voucher;

/**
 * DTO đại diện cho thông tin Voucher dùng trong màn hình quản trị Admin.
 */
final class AdminVoucherDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly ?string $name,
        public readonly int    $serviceType,
        public readonly int    $discountType,
        public readonly float  $discountValue,
        public readonly float  $minOrderAmount,
        public readonly ?float $maxDiscountAmount,
        public readonly string $validFrom,
        public readonly string $validUntil,
        public readonly ?int   $totalUsageLimit,
        public readonly int    $usedCount,
        public readonly bool   $isActive,
        public readonly string $description,
        public readonly string $createdAt,
    ) {
    }

    /**
     * Factory method để tạo DTO từ Model.
     */
    public static function fromModel(Voucher $voucher): self
    {
        return new self(
            id:                 (string) $voucher->id,
            code:               $voucher->code,
            name:               $voucher->name,
            serviceType:        $voucher->service_type->value,
            discountType:       $voucher->discount_type->value,
            discountValue:      $voucher->discount_value,
            minOrderAmount:     $voucher->min_order_amount,
            maxDiscountAmount:  $voucher->max_discount_amount,
            validFrom:          $voucher->valid_from->toDateTimeString(),
            validUntil:         $voucher->valid_until->toDateTimeString(),
            totalUsageLimit:    $voucher->total_usage_limit,
            usedCount:          (int) ($voucher->used_count ?? 0),
            isActive:           $voucher->is_active,
            description:        $voucher->description ?? '',
            createdAt:          $voucher->created_at?->toDateTimeString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'code'                => $this->code,
            'name'                => $this->name,
            'service_type'        => $this->serviceType,
            'discount_type'       => $this->discountType,
            'discount_value'      => $this->discountValue,
            'min_order_amount'    => $this->minOrderAmount,
            'max_discount_amount' => $this->maxDiscountAmount,
            'valid_from'          => $this->validFrom,
            'valid_until'         => $this->validUntil,
            'total_usage_limit'   => $this->totalUsageLimit,
            'used_count'          => $this->usedCount,
            'is_active'           => $this->isActive,
            'description'         => $this->description,
            'created_at'          => $this->createdAt,
        ];
    }
}
