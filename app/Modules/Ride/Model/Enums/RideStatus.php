<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

/**
 * Trạng thái vòng đời của một chuyến xe.
 * Enum có các domain methods để kiểm tra trạng thái và transition hợp lệ.
 */
enum RideStatus: int
{
    case DRAFT       = 1; // Nháp — chưa xác nhận
    case PENDING     = 2; // Đang tìm tài xế
    case ACCEPTED    = 3; // Tài xế đã nhận
    case IN_PROGRESS = 4; // Đang di chuyển
    case COMPLETED   = 5; // Hoàn thành
    case CANCELLED   = 6; // Đã hủy
    case PICKED_UP   = 7; // Đã đón khách / Đã lấy hàng

    /**
     * Kiểm tra xem trạng thái này có phải là trạng thái cuối không.
     * Trạng thái cuối = không thể chuyển sang trạng thái khác được nữa.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], strict: true);
    }

    /**
     * Kiểm tra xem có thể chuyển sang trạng thái tiếp theo không.
     * Áp dụng Finite State Machine pattern để đảm bảo transition hợp lệ.
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::DRAFT       => in_array($next, [self::PENDING, self::CANCELLED], strict: true),
            self::PENDING     => in_array($next, [self::ACCEPTED, self::CANCELLED], strict: true),
            self::ACCEPTED    => in_array($next, [self::PICKED_UP, self::CANCELLED], strict: true),
            self::PICKED_UP   => in_array($next, [self::IN_PROGRESS, self::CANCELLED], strict: true),
            self::IN_PROGRESS => $next === self::COMPLETED,
            default           => false, // COMPLETED và CANCELLED là terminal
        };
    }

    /**
     * Trả về nhãn hiển thị tiếng Việt của trạng thái.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT       => 'Nháp',
            self::PENDING     => 'Đang tìm tài xế',
            self::ACCEPTED    => 'Tài xế đã nhận',
            self::IN_PROGRESS => 'Đang di chuyển',
            self::COMPLETED   => 'Hoàn thành',
            self::CANCELLED   => 'Đã hủy',
            self::PICKED_UP   => 'Đã đón khách / Đã lấy hàng',
        };
    }
}
