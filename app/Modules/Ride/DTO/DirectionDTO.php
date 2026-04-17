<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

/**
 * DTO chứa thông tin dẫn đường (Route) từ Map Service.
 */
final class DirectionDTO
{
    public function __construct(
        public readonly int $distance,    // Khoảng cách (mét)
        public readonly int $duration,    // Thời gian dự kiến (giây)
        public readonly string $polyline, // Tọa độ đường đi (Encoded Polyline)
        public readonly array $bounds,    // Giới hạn bản đồ (viewport)
    ) {
    }

    public static function create(int $distance, int $duration, string $polyline, array $bounds = []): self
    {
        return new self($distance, $duration, $polyline, $bounds);
    }

    public function toArray(): array
    {
        return [
            'distance' => $this->distance,
            'duration' => $this->duration,
            'polyline' => $this->polyline,
            'bounds'   => $this->bounds,
        ];
    }
}
