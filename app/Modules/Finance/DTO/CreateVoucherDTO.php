<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\AdminCreateVoucherRequest;
use App\Modules\Finance\Model\Enums\VoucherDiscountType;
use App\Modules\Finance\Model\Enums\VoucherServiceType;

final class CreateVoucherDTO
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly VoucherServiceType $serviceType,
        public readonly VoucherDiscountType $discountType,
        public readonly float $discountValue,
        public readonly float $minOrderAmount,
        public readonly ?float $maxDiscountAmount,
        public readonly string $validFrom,
        public readonly string $validUntil,
        public readonly ?int $totalUsageLimit,
        public readonly bool $isActive,
        public readonly ?string $description,
    ) {
    }

    public static function fromRequest(AdminCreateVoucherRequest $request): self
    {
        return new self(
            code: $request->string('code')->toString(),
            name: $request->string('name')->toString(),
            serviceType: VoucherServiceType::from((int) $request->input('service_type')),
            discountType: VoucherDiscountType::from((int) $request->input('discount_type')),
            discountValue: (float) $request->input('discount_value'),
            minOrderAmount: (float) $request->input('min_order_amount', 0),
            maxDiscountAmount: $request->has('max_discount_amount') ? (float) $request->input('max_discount_amount') : null,
            validFrom: $request->string('valid_from')->toString(),
            validUntil: $request->string('valid_until')->toString(),
            totalUsageLimit: $request->has('total_usage_limit') ? (int) $request->input('total_usage_limit') : null,
            isActive: $request->boolean('is_active', true),
            description: $request->string('description')->toString(),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'service_type' => $this->serviceType->value,
            'discount_type' => $this->discountType->value,
            'discount_value' => $this->discountValue,
            'min_order_amount' => $this->minOrderAmount,
            'max_discount_amount' => $this->maxDiscountAmount,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'total_usage_limit' => $this->totalUsageLimit,
            'is_active' => $this->isActive,
            'description' => $this->description,
        ];
    }
}
