<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use App\Modules\Merchant\Http\Requests\RegisterMerchantRequest;

final class RegisterMerchantDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $fullName,
        public readonly string $phone,
        public readonly string $citizenId,
        public readonly string $storeName,
        public readonly string $storeAddress,
        public readonly string $businessType,
        public readonly string $citizenIdImage,
        public readonly ?string $businessLicenseImage,
        public readonly string $storeImage,
    ) {}

    public static function fromRequest(RegisterMerchantRequest $request): self
    {
        return new self(
            userId: (string) $request->user()->id,
            fullName: $request->string('full_name')->toString(),
            phone: $request->string('phone')->toString(),
            citizenId: $request->string('citizen_id')->toString(),
            storeName: $request->string('store_name')->toString(),
            storeAddress: $request->string('store_address')->toString(),
            businessType: $request->string('business_type')->toString(),
            citizenIdImage: $request->string('citizen_id_image')->toString(),
            businessLicenseImage: $request->string('business_license_image')->toString() ?: null,
            storeImage: $request->string('store_image')->toString(),
        );
    }
}
