<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use App\Modules\User\Http\Requests\Admin\UpdateDriverRequest;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\Gender;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\VehicleColor;
use App\Modules\User\Model\Enums\VehicleType;

final class UpdateDriverDTO
{
    public function __construct(
        public readonly string|int $userId,
        public readonly string $fullName,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?Gender $gender = null,
        public readonly ?string $birthday = null,
        public readonly ?string $address = null,
        public readonly ?DriverGroupType $driverGroupType = null,
        public readonly ?VehicleType $vehicleType = null,
        public readonly ?VehicleColor $vehicleColor = null,
        public readonly ?string $vehicleName = null,
        public readonly ?string $vehicleNumber = null,
        public readonly ?bool $isActive = null,
        public readonly ?KycStatus $kycStatus = null,
        public readonly ?string $lockReason = null,
    ) {}

    public static function fromRequest(UpdateDriverRequest $request, string|int $userId): self
    {
        return new self(
            userId: $userId,
            fullName: $request->string('full_name')->toString(),
            phone: $request->string('phone')->toString(),
            email: $request->input('email'),
            gender: $request->filled('gender') ? Gender::from((int) $request->input('gender')) : null,
            birthday: $request->input('birthday'),
            address: $request->input('address'),
            driverGroupType: $request->filled('driver_group_type') ? DriverGroupType::from((int) $request->input('driver_group_type')) : null,
            vehicleType: $request->filled('vehicle_type') ? VehicleType::from((int) $request->input('vehicle_type')) : null,
            vehicleColor: $request->filled('vehicle_color') ? VehicleColor::from((int) $request->input('vehicle_color')) : null,
            vehicleName: $request->input('vehicle_name'),
            vehicleNumber: $request->input('vehicle_number'),
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            kycStatus: $request->filled('kyc_status') ? KycStatus::from((int) $request->input('kyc_status')) : null,
            lockReason: $request->input('lock_reason'),
        );
    }
}
