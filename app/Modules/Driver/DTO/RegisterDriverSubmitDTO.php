<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\RegisterDriverSubmitRequest;
use App\Modules\Driver\Model\Enums\VehicleColor;
use Illuminate\Http\UploadedFile;

/**
 * DTO cho UC-30: nộp tài liệu + tạo hồ sơ.
 *
 * @property array<string, UploadedFile> $files
 */
final class RegisterDriverSubmitDTO
{
    public function __construct(
        public readonly string       $userId,
        public readonly string       $fullName,
        public readonly string       $phone,
        public readonly string       $citizenId,
        public readonly int          $vehicleType,
        public readonly string       $vehicleName,
        public readonly VehicleColor $vehicleColor,
        public readonly string       $vehicleNumber,
        public readonly int          $vehicleYear,
        public readonly array        $services,
        public readonly array        $files,
    ) {}

    public static function fromRequest(RegisterDriverSubmitRequest $request): self
    {
        return new self(
            userId:        (string) $request->user()->id,
            fullName:      $request->string('full_name')->toString(),
            phone:         $request->string('phone')->toString(),
            citizenId:     $request->string('citizen_id')->toString(),
            vehicleType:   (int) $request->input('vehicle_type'),
            vehicleName:   $request->string('vehicle_name')->toString(),
            vehicleColor:  VehicleColor::from((int) $request->input('vehicle_color')),
            vehicleNumber: $request->string('vehicle_number')->toString(),
            vehicleYear:   (int) $request->input('vehicle_year'),
            services:      (array) $request->input('services', []),
            files: [
                'cccd_front'      => $request->file('cccd_front'),
                'cccd_back'       => $request->file('cccd_back'),
                'driver_license'  => $request->file('driver_license'),
                'vehicle_reg'     => $request->file('vehicle_reg'),
                'criminal_record' => $request->file('criminal_record'),
                'health_cert'     => $request->file('health_cert'),
                'portrait'        => $request->file('portrait'),
                'insurance'       => $request->file('insurance'),
            ],
        );
    }
}
