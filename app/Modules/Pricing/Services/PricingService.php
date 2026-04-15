<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\DTO\PricingResultDTO;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;

final class PricingService extends BaseService implements PricingServiceInterface
{
    private const RATE_CONFIG = [
        1 => [ // BIKE
            'base_fare'     => 12000.0,
            'min_fare'      => 15000.0,
            'distance_rate' => 4000.0,
            'time_rate'     => 300.0,
        ],
        2 => [ // CAR_4_SEATS
            'base_fare'     => 25000.0,
            'min_fare'      => 30000.0,
            'distance_rate' => 10000.0,
            'time_rate'     => 500.0,
        ],
        3 => [ // CAR_7_SEATS
            'base_fare'     => 30000.0,
            'min_fare'      => 35000.0,
            'distance_rate' => 12000.0,
            'time_rate'     => 600.0,
        ],
        4 => [ // CAR_9_SEATS
            'base_fare'     => 40000.0,
            'min_fare'      => 45000.0,
            'distance_rate' => 15000.0,
            'time_rate'     => 700.0,
        ],
    ];

    public function calculatePrice(PricingRequestDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): PricingResultDTO {
            $config = self::RATE_CONFIG[$dto->vehicleType] ?? self::RATE_CONFIG[1];

            $baseFare     = (float) $config['base_fare'];
            $minFare      = (float) $config['min_fare'];
            $distanceFare = (float) $dto->distance * (float) $config['distance_rate'];
            $timeFare     = (float) $dto->duration * (float) $config['time_rate'];

            // Giá vé cơ bản = Giá cơ bản + (Khoảng cách × Giá/km) + (Thời gian × Giá/phút)
            $baseTotalFare = $baseFare + $distanceFare + $timeFare;

            // Giá vé tăng đột biến = Giá vé cơ bản × Hệ số tăng đột biến
            $surgeMultiplier = (float) $dto->surgeMultiplier;
            $surgeFare       = $baseTotalFare * $surgeMultiplier;

            // Giá cuối cùng = max(Giá tăng đột biến, Giá tối thiểu), làm tròn 1000 VND
            $finalFare = round(max($surgeFare, $minFare) / 1000) * 1000;

            return PricingResultDTO::create(
                baseFare:        $baseFare, // Giá vé cơ bản
                distanceFare:    $distanceFare, // Giá vé tăng đột biến
                timeFare:        $timeFare, // Giá vé tăng đột biến theo thời gian
                surgeMultiplier: $surgeMultiplier, // Hệ số tăng đột biến
                originalFare:    $baseTotalFare, // Giá vé cơ bản + (Khoảng cách × Giá/km) + (Thời gian × Giá/phút)
                finalFare:       $finalFare, // Giá cuối cùng
            );
        });
    }
}
