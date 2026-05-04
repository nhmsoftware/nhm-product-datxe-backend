<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

/**
 * Trạng thái chi tiết trong quá trình tracking chuyến xe.
 * Phục vụ hiển thị phía Mobile Frontend (UC-09, UC-10, UC-12, UC-15, UC-22).
 */
enum RideTrackingStatus: int
{
    case WAITING_DRIVER    = 1;  // Đang đợi tài xế nhận chuyến
    case DRIVER_ACCEPTED   = 2;  // Tài xế đã nhận chuyến
    case DRIVER_EN_ROUTE   = 3;  // Tài xế đang trên đường đón
    case DRIVER_ARRIVED    = 4;  // Tài xế đã đến điểm đón
    case DRIVER_CANCELLED  = 5;  // Tài xế đã hủy chuyến
    case CUSTOMER_CANCELLED = 6;  // Khách hàng đã hủy chuyến
    case TRACKING_LOST     = 7;  // Mất tín hiệu định vị tài xế

    /**
     * Trả về nhãn hiển thị tiếng Việt của trạng thái tracking.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::WAITING_DRIVER    => 'Đang tìm tài xế',
            self::DRIVER_ACCEPTED   => 'Tài xế đã nhận chuyến',
            self::DRIVER_EN_ROUTE   => 'Tài xế đang đến',
            self::DRIVER_ARRIVED    => 'Tài xế đã đến nơi',
            self::DRIVER_CANCELLED  => 'Tài xế đã hủy chuyến',
            self::CUSTOMER_CANCELLED => 'Bạn đã hủy chuyến',
            self::TRACKING_LOST     => 'Tín hiệu yếu...',
        };
    }
}
