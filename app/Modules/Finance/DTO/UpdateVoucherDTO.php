<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\AdminUpdateVoucherRequest;
use App\Modules\Finance\Model\Enums\VoucherDiscountType;
use App\Modules\Finance\Model\Enums\VoucherServiceType;

final class UpdateVoucherDTO
{
    public function __construct(
        public readonly ?string $code,
        public readonly ?string $name,
        public readonly ?VoucherServiceType $serviceType,
        public readonly ?VoucherDiscountType $discountType,
        public readonly ?float $discountValue,
        public readonly ?float $minOrderAmount,
        public readonly ?float $maxDiscountAmount,
        public readonly ?string $validFrom,
        public readonly ?string $validUntil,
        public readonly ?int $totalUsageLimit,
        public readonly ?bool $isActive,
        public readonly ?string $description,
    ) {
    }

    public static function fromRequest(AdminUpdateVoucherRequest $request): self
    {
        return new self(
            code: $request->has('code') ? $request->string('code')->toString() : null,
            name: $request->has('name') ? $request->string('name')->toString() : null,
            serviceType: $request->has('service_type') ? VoucherServiceType::from((int) $request->input('service_type')) : null,
            discountType: $request->has('discount_type') ? VoucherDiscountType::from((int) $request->input('discount_type')) : null,
            discountValue: $request->has('discount_value') ? (float) $request->input('discount_value') : null,
            minOrderAmount: $request->has('min_order_amount') ? (float) $request->input('min_order_amount') : null,
            maxDiscountAmount: $request->has('max_discount_amount') ? (float) $request->input('max_discount_amount') : null,
            validFrom: $request->has('valid_from') ? $request->string('valid_from')->toString() : null,
            validUntil: $request->has('valid_until') ? $request->string('valid_until')->toString() : null,
            totalUsageLimit: $request->has('total_usage_limit') ? (int) $request->input('total_usage_limit') : null,
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            description: $request->has('description') ? $request->string('description')->toString() : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'name' => $this->name,
            'service_type' => $this->serviceType?->value,
            'discount_type' => $this->discountType?->value,
            'discount_value' => $this->discountValue,
            'min_order_amount' => $this->minOrderAmount,
            'max_discount_amount' => $this->maxDiscountAmount,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'total_usage_limit' => $this->totalUsageLimit,
            'is_active' => $this->isActive,
            'description' => $this->description,
        ], fn($value) => $value !== null);
    }
}
