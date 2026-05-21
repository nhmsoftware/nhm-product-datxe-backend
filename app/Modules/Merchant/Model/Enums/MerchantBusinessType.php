<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Model\Enums;

enum MerchantBusinessType: int
{
    case RESTAURANT = 1;
    case CAFE = 2;
    case MILK_TEA = 3;
    case FAST_FOOD = 4;
    case STREET_FOOD = 5;
    case BAKERY = 6;
    case GROCERY = 7;
    case OTHER = 8;

    public function getLabel(): string
    {
        return match ($this) {
            self::RESTAURANT => 'Nhà hàng',
            self::CAFE => 'Cà phê',
            self::MILK_TEA => 'Trà sữa',
            self::FAST_FOOD => 'Đồ ăn nhanh',
            self::STREET_FOOD => 'Đồ ăn đường phố',
            self::BAKERY => 'Tiệm bánh',
            self::GROCERY => 'Tạp hóa',
            self::OTHER => 'Khác',
        };
    }

    public static function options(): array
    {
        return array_map(
            static fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->getLabel(),
            ],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_map(static fn (self $type): int => $type->value, self::cases());
    }
}
