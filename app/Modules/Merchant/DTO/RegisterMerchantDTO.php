<?php

declare(strict_types=1);

namespace App\Modules\Merchant\DTO;

use App\Modules\Merchant\Http\Requests\RegisterMerchantRequest;
use App\Modules\Merchant\Model\Enums\MerchantBusinessType;
use Illuminate\Http\UploadedFile;

final class RegisterMerchantDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $fullName,
        public readonly string $phone,
        public readonly string $citizenId,
        public readonly string $storeName,
        public readonly string $storeAddress,
        public readonly float  $latitude,
        public readonly float  $longitude,
        public readonly MerchantBusinessType $businessType,
        public readonly array  $files,
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
            latitude: (float) $request->input('latitude'),
            longitude: (float) $request->input('longitude'),
            businessType: MerchantBusinessType::from($request->integer('business_type')),
            files: [
                'citizen_id_image'       => $request->file('citizen_id_image'),
                'business_license_image' => $request->file('business_license_image'),
                'store_image'            => $request->file('store_image'),
            ],
        );
    }
}
