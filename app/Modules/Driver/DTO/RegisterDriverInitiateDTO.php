<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\RegisterDriverInitiateRequest;
use App\Modules\Driver\Model\Enums\VehicleColor;
use App\Modules\Ride\Model\Enums\VehicleType;

/**
 * DTO cho bước 1 UC-30: Validate thông tin + gửi OTP.
 * Không chứa file — file chỉ được submit ở bước 2.
 */
final class RegisterDriverInitiateDTO
{
    public function __construct(
        public readonly int          $userId,
        public readonly string       $fullName,
        public readonly string       $phone,
        public readonly string       $citizenId,
        public readonly VehicleType  $vehicleType,
        public readonly string       $vehicleName,
        public readonly VehicleColor $vehicleColor,
        public readonly string       $vehicleNumber,
        public readonly int          $vehicleYear,
    ) {}

    public static function fromRequest(RegisterDriverInitiateRequest $request): self
    {
        return new self(
            userId:        (int) $request->user()->id,
            fullName:      $request->string('full_name')->toString(),
            phone:         $request->string('phone')->toString(),
            citizenId:     $request->string('citizen_id')->toString(),
            vehicleType:   VehicleType::from((int) $request->input('vehicle_type')),
            vehicleName:   $request->string('vehicle_name')->toString(),
            vehicleColor:  VehicleColor::from((int) $request->input('vehicle_color')),
            vehicleNumber: $request->string('vehicle_number')->toString(),
            vehicleYear:   (int) $request->input('vehicle_year'),
        );
    }
}
