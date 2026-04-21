<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

/**
 * DTO chứa thông tin chi tiết giá cước hiển thị cho khách hàng (UC-10).
 * Bao gồm các thành phần cấu thành giá và giá cuối cùng sau khi áp dụng voucher.
 */
final class PriceEstimateDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly float  $distanceKm,
        public readonly int    $durationMinutes,
        public readonly float  $baseFare,
        public readonly float  $distanceFare,
        public readonly float  $timeFare,
        public readonly float  $surgeMultiplier,
        public readonly float  $originalFare,
        public readonly float  $finalFare,
        public readonly ?string $voucherCode   = null,
        public readonly float  $discountAmount = 0.0,
    ) {
    }

    public static function create(
        string $rideId,
        float  $distanceKm,
        int    $durationMinutes,
        float  $baseFare,
        float  $distanceFare,
        float  $timeFare,
        float  $surgeMultiplier,
        float  $originalFare,
        float  $finalFare,
        ?string $voucherCode   = null,
        float  $discountAmount = 0.0,
    ): self {
        return new self(
            $rideId,
            $distanceKm,
            $durationMinutes,
            $baseFare,
            $distanceFare,
            $timeFare,
            $surgeMultiplier,
            $originalFare,
            $finalFare,
            $voucherCode,
            $discountAmount
        );
    }

    public function toArray(): array
    {
        return [
            'ride_id'           => $this->rideId,
            'distance_km'       => round($this->distanceKm, 2),
            'duration_minutes'  => $this->durationMinutes,
            'fare_breakdown'    => [
                'base_fare'          => $this->baseFare,
                'distance_fare'      => $this->distanceFare,
                'time_fare'          => $this->timeFare,
                'surge_multiplier'   => $this->surgeMultiplier,
                'original_fare'      => $this->originalFare,
            ],
            'voucher_code'      => $this->voucherCode,
            'discount_amount'   => $this->discountAmount,
            'final_fare'        => $this->finalFare,
        ];
    }
}
