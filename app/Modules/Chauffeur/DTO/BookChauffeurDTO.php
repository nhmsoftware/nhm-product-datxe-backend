<?php

declare(strict_types=1);

namespace App\Modules\Chauffeur\DTO;

use App\Modules\Chauffeur\Http\Requests\BookChauffeurRequest;

/**
 * DTO cho request đặt dịch vụ Lái hộ (UC-124).
 */
final class BookChauffeurDTO
{
    public function __construct(
        public readonly string  $customerId,
        public readonly string  $pickupAddress,
        public readonly float   $pickupLat,
        public readonly float   $pickupLng,
        public readonly string  $destinationAddress,
        public readonly float   $destinationLat,
        public readonly float   $destinationLng,
        public readonly string  $licensePlate,
        public readonly string  $carType,
        public readonly string  $carBrand,
        public readonly string  $carColor,
        public readonly ?string $pickupTime = null,
        public readonly ?string $voucherCode = null,
    ) {
    }

    /**
     * Khởi tạo DTO từ FormRequest đã validate.
     */
    public static function fromRequest(BookChauffeurRequest $request): self
    {
        return new self(
            customerId:         (string) $request->user()->id,
            pickupAddress:      $request->string('pickup_address')->toString(),
            pickupLat:          (float) $request->input('pickup_lat'),
            pickupLng:          (float) $request->input('pickup_lng'),
            destinationAddress: $request->string('destination_address')->toString(),
            destinationLat:     (float) $request->input('destination_lat'),
            destinationLng:     (float) $request->input('destination_lng'),
            licensePlate:       $request->string('license_plate')->toString(),
            carType:            $request->string('car_type')->toString(),
            carBrand:           $request->string('car_brand')->toString(),
            carColor:           $request->string('car_color')->toString(),
            pickupTime:         $request->input('pickup_time'),
            voucherCode:        $request->string('voucher_code')->trim()->isEmpty() ? null : $request->string('voucher_code')->trim()->toString(),
        );
    }
}
