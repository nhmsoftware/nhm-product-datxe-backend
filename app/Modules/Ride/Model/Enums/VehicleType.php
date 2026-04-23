<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

/**
 * Loại phương tiện hỗ trợ đặt xe.
 * Enum tập trung toàn bộ metadata của loại xe, tránh rải rác logic trong Service.
 */
enum VehicleType: int
{
    case BIKE        = 1;
    case CAR_4_SEATS = 2;
    case CAR_7_SEATS = 3;
    case CAR_9_SEATS = 4;
    case CAR_SHARED  = 5;

    /**
     * Trả về tên hiển thị của loại xe.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::BIKE        => 'Xe Máy',
            self::CAR_4_SEATS => 'Ô Tô 4 Chỗ',
            self::CAR_7_SEATS => 'Ô Tô 7 Chỗ',
            self::CAR_9_SEATS => 'Ô Tô 9 Chỗ',
            self::CAR_SHARED  => 'Xe Ghép (Liên tỉnh)',
        };
    }

    /**
     * Trả về mô tả ngắn gọn về loại xe.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::BIKE        => 'Nhanh, tiết kiệm — phù hợp đường ngắn',
            self::CAR_4_SEATS => 'Thoải mái cho 1–3 hành khách',
            self::CAR_7_SEATS => 'Rộng rãi cho nhóm 4–6 người',
            self::CAR_9_SEATS => 'Lý tưởng cho nhóm đông hoặc nhiều hành lý',
            self::CAR_SHARED  => 'Tiết kiệm, đi chung với hành khách khác',
        };
    }

    /**
     * Trả về số chỗ ngồi tối đa (không tính tài xế).
     */
    public function getCapacity(): int
    {
        return match ($this) {
            self::BIKE        => 1,
            self::CAR_4_SEATS => 3,
            self::CAR_7_SEATS => 6,
            self::CAR_9_SEATS => 8,
            self::CAR_SHARED  => 1,
        };
    }

    /**
     * Trả về thời gian chờ ước tính.
     * TODO: Thay bằng tính toán realtime dựa trên vị trí tài xế gần nhất.
     */
    public function getEstimatedWaitTime(): string
    {
        return match ($this) {
            self::BIKE        => '2–5 phút',
            self::CAR_4_SEATS => '3–7 phút',
            self::CAR_7_SEATS => '5–10 phút',
            self::CAR_9_SEATS => '7–15 phút',
            self::CAR_SHARED  => 'Theo lịch hẹn',
        };
    }
}
