<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\DTO\PricingResultDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Ride\Model\Enums\VehicleType;

final class PricingService extends BaseService implements PricingServiceInterface
{
    /**
     * Cấu hình tĩnh để định giá xe.
     * Trong hệ thống sản xuất, điều này phải được tìm nạp từ cơ sở dữ liệu (ví dụ: pricing_configs).
     */
    private const RATE_CONFIG = [
        1 => [ // BIKE
            'base_distance' => 2.0,      // 2 km đầu tiên
            'base_fare' => 12000.0,      // 12k VND đầu tiên
            'distance_rate' => 4000.0,   // 4k VND/km sau đầu tiên
            'time_rate' => 300.0,        // 300 VND/ phút
        ],
        2 => [ // CAR_4_SEATS
            'base_distance' => 2.0,
            'base_fare' => 25000.0,
            'distance_rate' => 10000.0,
            'time_rate' => 500.0,
        ],
        3 => [ // CAR_7_SEATS
            'base_distance' => 2.0,
            'base_fare' => 30000.0,
            'distance_rate' => 12000.0,
            'time_rate' => 600.0,
        ],
        4 => [ // CAR_9_SEATS
            'base_distance' => 2.0,
            'base_fare' => 40000.0,
            'distance_rate' => 15000.0,
            'time_rate' => 700.0,
        ],
    ];

    /**
     * Tính giá vé theo config.
     */
    public function calculatePrice(PricingRequestDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): PricingResultDTO {
            // Dự phòng cho BIKE (1) nếu không tìm thấy loại xe
            $config = self::RATE_CONFIG[$dto->vehicleType] ?? self::RATE_CONFIG[1];

            $baseDistance = (float) $config['base_distance'];
            $baseFare     = (float) $config['base_fare'];

            // Tính giá vé cự ly (chỉ dành cho quãng đường vượt quá khoảng cách cơ sở)
            $chargeableDistance = (float) max(0, $dto->distance - $baseDistance);
            $distanceFare       = $chargeableDistance * (float) $config['distance_rate'];

            // Tính giá vé thời gian (dựa vào thời gian phút)
            $timeFare = (float) $dto->duration * (float) $config['time_rate'];

            // Tính giá vé ban đầu (trước khi áp dụng surge multiplier)
            $originalFare = $baseFare + $distanceFare + $timeFare;

            // Áp dụng surge multiplier
            $finalFare = $originalFare * (float) $dto->surgeMultiplier;

            // Làm tròn giá vé cuối cùng (số lượng VND gần nhất)
            $finalFare = round($finalFare / 1000) * 1000;

            return PricingResultDTO::create(
                baseFare: $baseFare,
                distanceFare: $distanceFare,
                timeFare: $timeFare,
                surgeMultiplier: (float) $dto->surgeMultiplier,
                originalFare: $originalFare,
                finalFare: $finalFare
            );
        });
    }
}
