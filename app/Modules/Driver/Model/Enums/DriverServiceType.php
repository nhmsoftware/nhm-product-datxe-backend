<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model\Enums;

/**
 * Danh sách các dịch vụ mà tài xế có thể đăng ký hoạt động (UC-30).
 */
enum DriverServiceType: int
{
    case BIKE_RIDE       = 1; // Xe ôm
    case TAXI_4_SEATS    = 2; // Taxi 4 chỗ
    case TAXI_7_SEATS    = 3; // Taxi 7 chỗ
    case FOOD_DELIVERY   = 4; // Giao đồ ăn
    case PARCEL_DELIVERY = 5; // Giao hàng
    case INTERCITY       = 6; // Xe đi tỉnh
    case AIRPORT         = 7; // Xe sân bay
    case DRIVER_FOR_HIRE = 8; // Lái hộ

    /**
     * Compatibility map for fixed vehicle type IDs in phase 2.
     *
     * @var array<int, string>
     */
    private const VEHICLE_TYPE_LABELS = [
        1 => 'Xe máy',
        2 => 'Ô tô 4 chỗ',
        3 => 'Ô tô 7 chỗ',
        4 => 'Ô tô 9 chỗ',
        5 => 'Xe ghép / Tiện chuyến',
        6 => 'Lái hộ',
    ];

    /**
     * Trả về tên hiển thị của dịch vụ.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BIKE_RIDE       => 'Xe ôm',
            self::TAXI_4_SEATS    => 'Taxi 4 chỗ',
            self::TAXI_7_SEATS    => 'Taxi 7 chỗ',
            self::FOOD_DELIVERY   => 'Giao đồ ăn',
            self::PARCEL_DELIVERY => 'Giao hàng',
            self::INTERCITY       => 'Xe đi tỉnh',
            self::AIRPORT         => 'Xe sân bay',
            self::DRIVER_FOR_HIRE => 'Lái hộ',
        };
    }

    /**
     * Trả về danh sách loại xe hỗ trợ dịch vụ này (raw int values).
     * Cần đồng bộ với canonical vehicle_types IDs.
     */
    private function getSupportedVehicleTypeValues(): array
    {
        return match ($this) {
            self::BIKE_RIDE       => [1],
            self::TAXI_4_SEATS    => [2],
            self::TAXI_7_SEATS    => [3],
            self::FOOD_DELIVERY   => [1],
            self::PARCEL_DELIVERY => [1, 2, 3, 4],
            self::INTERCITY       => [2, 3, 4, 5],
            self::AIRPORT         => [2, 3, 4],
            self::DRIVER_FOR_HIRE => [1, 2, 3, 4],
        };
    }

    /**
     * Trả về danh sách loại xe hỗ trợ kèm label để frontend hiển thị.
     */
    public function getSupportedVehicleTypes(): array
    {
        return array_values(array_filter(
            array_map(function (int $id) {
                $label = self::VEHICLE_TYPE_LABELS[$id] ?? null;
                if ($label === null) {
                    return null;
                }

                return ['id' => $id, 'label' => $label];
            }, $this->getSupportedVehicleTypeValues()),
            fn ($item) => $item !== null
        ));
    }

    /**
     * Kiểm tra dịch vụ này có hỗ trợ loại xe không.
     */
    public function supportsVehicleType(int $vehicleTypeId): bool
    {
        return in_array($vehicleTypeId, $this->getSupportedVehicleTypeValues(), strict: true);
    }

    /**
     * Trả về toàn bộ danh sách dịch vụ kèm label để Frontend hiển thị.
     */
    public static function getList(): array
    {
        $list = [];
        foreach (self::cases() as $case) {
            $list[] = [
                'id'                      => $case->value,
                'label'                   => $case->getLabel(),
                'supported_vehicle_types' => $case->getSupportedVehicleTypes(),
            ];
        }
        return $list;
    }

    /**
     * Trả về danh sách dịch vụ mà một loại xe cụ thể có thể đăng ký.
     *
     * @param int $vehicleTypeId  ID loại xe (VehicleType enum value: 1=Xe máy, 2=Ô tô 4 chỗ...)
     */
    public static function getListByVehicleType(int $vehicleTypeId): array
    {
        $list = [];
        foreach (self::cases() as $case) {
            if ($case->supportsVehicleType($vehicleTypeId)) {
                $list[] = [
                    'id'    => $case->value,
                    'label' => $case->getLabel(),
                ];
            }
        }
        return $list;
    }
}
