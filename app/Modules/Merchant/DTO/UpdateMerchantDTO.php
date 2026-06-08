<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use App\Modules\Merchant\Http\Requests\Admin\UpdateMerchantRequest;
use App\Modules\Merchant\Model\Enums\MerchantBusinessType;
use App\Modules\User\Model\Enums\KycStatus;

final class UpdateMerchantDTO
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $ownerName,
        public readonly string $phone,
        public readonly ?string $email,
        public readonly string $storeName,
        public readonly string $storeAddress,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?MerchantBusinessType $businessType,
        public readonly ?string $businessLicense,
        public readonly ?KycStatus $status,
        public readonly ?bool $isActive,
        public readonly ?string $lockReason,
        public readonly ?string $openingTime,
        public readonly ?string $closingTime,
        public readonly array $files,
    ) {}

    public static function fromRequest(CreateMerchantRequest|UpdateMerchantRequest $request, string $merchantId): self
    {
        return new self(
            merchantId: $merchantId,
            ownerName: $request->string('owner_name')->toString(),
            phone: $request->string('phone')->toString(),
            email: $request->input('email'),
            storeName: $request->string('store_name')->toString(),
            storeAddress: $request->string('store_address')->toString(),
            latitude: $request->filled('latitude') ? (float) $request->input('latitude') : null,
            longitude: $request->filled('longitude') ? (float) $request->input('longitude') : null,
            businessType: $request->filled('business_type') ? MerchantBusinessType::from((int) $request->input('business_type')) : null,
            businessLicense: $request->input('business_license'),
            status: $request->filled('status') ? KycStatus::from((int) $request->input('status')) : null,
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            lockReason: $request->input('lock_reason'),
            openingTime: $request->input('opening_time'),
            closingTime: $request->input('closing_time'),
            files: [
                'business_license_image' => $request->file('business_license_image'),
                'store_image' => $request->file('store_image'),
            ],
        );
    }
}
