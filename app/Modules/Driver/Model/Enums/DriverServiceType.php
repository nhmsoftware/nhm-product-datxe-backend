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
     * Trả về toàn bộ danh sách dịch vụ kèm label để Frontend hiển thị.
     */
    public static function getList(): array
    {
        $list = [];
        foreach (self::cases() as $case) {
            $list[] = [
                'id'    => $case->value,
                'label' => $case->getLabel(),
            ];
        }
        return $list;
    }
}
