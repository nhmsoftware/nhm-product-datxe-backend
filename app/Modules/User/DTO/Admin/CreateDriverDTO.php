<?php

declare(strict_types=1);

namespace App\Modules\User\DTO\Admin;

use App\Modules\User\Http\Requests\Admin\CreateDriverRequest;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\Gender;
use App\Modules\User\Model\Enums\VehicleColor;
use App\Modules\User\Model\Enums\VehicleType;

final class CreateDriverDTO
{
    public function __construct(
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
        public readonly ?string $password = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $note = null,
    ) {}

    public static function fromRequest(CreateDriverRequest $request): self
    {
        return new self(
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
            password: $request->input('password'),
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            note: $request->input('note'),
        );
    }
}
