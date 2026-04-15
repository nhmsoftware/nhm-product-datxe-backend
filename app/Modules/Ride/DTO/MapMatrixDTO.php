<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

/**
 * DTO chứa kết quả tính toán khoảng cách và thời gian từ bản đồ.
 */
final class MapMatrixDTO
{
    public function __construct(
        public readonly int $distance, // mét
        public readonly int $duration, // giây
    ) {
    }

    public static function create(int $distance, int $duration): self
    {
        return new self($distance, $duration);
    }

    public function toArray(): array
    {
        return [
            'distance' => $this->distance,
            'duration' => $this->duration,
        ];
    }
}
