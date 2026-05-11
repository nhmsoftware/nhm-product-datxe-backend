<?php

declare(strict_types=1);

namespace App\Modules\Driver\Model\Enums;

/**
 * FileableType — ánh xạ loại tài liệu với polymorphic `files` table (database.md G5).
 * UC-30: CCCD, bằng lái, đăng ký xe, lý lịch tư pháp, sức khỏe, chân dung, bảo hiểm.
 */
enum FileableType: int
{
    case AVATAR                                    = 1;
    case DRIVER_REVIEW_CCCD_FRONT                  = 2;
    case DRIVER_REVIEW_CCCD_BACK                   = 3;
    case DRIVER_REVIEW_LICENSE                     = 4;
    case DRIVER_REVIEW_VEHICLE_REG                 = 5;
    case DRIVER_REVIEW_CRIMINAL_RECORD             = 6;
    case DRIVER_REVIEW_HEALTH_CERT                 = 7;
    case DRIVER_REVIEW_PORTRAIT                    = 8;
    case DRIVER_REVIEW_INSURANCE                   = 9;
    case MERCHANT_REVIEW_CCCD                      = 10;
    case MERCHANT_REVIEW_BUSINESS_LICENSE          = 11;
    case MERCHANT_REVIEW_STORE_IMAGE               = 12;

    public function getLabel(): string
    {
        return match ($this) {
            self::AVATAR                        => 'Ảnh đại diện',
            self::DRIVER_REVIEW_CCCD_FRONT      => 'CCCD mặt trước',
            self::DRIVER_REVIEW_CCCD_BACK       => 'CCCD mặt sau',
            self::DRIVER_REVIEW_LICENSE         => 'Bằng lái xe',
            self::DRIVER_REVIEW_VEHICLE_REG     => 'Giấy đăng ký xe',
            self::DRIVER_REVIEW_CRIMINAL_RECORD => 'Lý lịch tư pháp',
            self::DRIVER_REVIEW_HEALTH_CERT     => 'Giấy khám sức khỏe',
            self::DRIVER_REVIEW_PORTRAIT        => 'Ảnh chân dung',
            self::DRIVER_REVIEW_INSURANCE       => 'Bảo hiểm trách nhiệm dân sự',
            self::MERCHANT_REVIEW_CCCD          => 'Ảnh CCCD',
            self::MERCHANT_REVIEW_BUSINESS_LICENSE => 'Giấy phép kinh doanh',
            self::MERCHANT_REVIEW_STORE_IMAGE   => 'Ảnh cửa hàng',
        };
    }

    /** Danh sách tài liệu bắt buộc cho UC-30 driver registration. */
    public static function requiredForDriverRegistration(): array
    {
        return [
            self::DRIVER_REVIEW_CCCD_FRONT,
            self::DRIVER_REVIEW_CCCD_BACK,
            self::DRIVER_REVIEW_LICENSE,
            self::DRIVER_REVIEW_VEHICLE_REG,
            self::DRIVER_REVIEW_CRIMINAL_RECORD,
            self::DRIVER_REVIEW_HEALTH_CERT,
            self::DRIVER_REVIEW_PORTRAIT,
            self::DRIVER_REVIEW_INSURANCE,
        ];
    }

    /** Danh sách tài liệu bắt buộc cho UC-52 merchant registration. */
    public static function requiredForMerchantRegistration(): array
    {
        return [
            self::MERCHANT_REVIEW_CCCD,
            self::MERCHANT_REVIEW_STORE_IMAGE,
        ];
    }

    public function getRegisterKey(): ?string
    {
        return match ($this) {
            self::DRIVER_REVIEW_CCCD_FRONT      => 'cccd_front',
            self::DRIVER_REVIEW_CCCD_BACK       => 'cccd_back',
            self::DRIVER_REVIEW_LICENSE         => 'driver_license',
            self::DRIVER_REVIEW_VEHICLE_REG     => 'vehicle_reg',
            self::DRIVER_REVIEW_CRIMINAL_RECORD => 'criminal_record',
            self::DRIVER_REVIEW_HEALTH_CERT     => 'health_cert',
            self::DRIVER_REVIEW_PORTRAIT        => 'portrait',
            self::DRIVER_REVIEW_INSURANCE       => 'insurance',
            default                             => null,
        };
    }
}
