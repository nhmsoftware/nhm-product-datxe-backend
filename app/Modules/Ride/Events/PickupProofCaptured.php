<?php

declare(strict_types=1);

namespace App\Modules\Ride\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-37: Sự kiện xảy ra khi Driver đã xác nhận lấy hàng (chụp ảnh bằng chứng).
 * Payload đầy đủ để Listener relay sang Node.js mà không cần query thêm.
 */
final class PickupProofCaptured
{
    use Dispatchable, SerializesModels;

    public function __construct(
        /** ID chuyến xe */
        public readonly string $rideId,
        /** ID tài xế */
        public readonly string $driverId,
        /** ID khách hàng (để notify) */
        public readonly string $customerId,
        /** URL ảnh đã upload (null nếu A3/A6 không chụp được) */
        public readonly ?string $photoUrl,
        /** Thời điểm chụp/xác nhận (ISO 8601) */
        public readonly string $capturedAt,
        /** GPS tại thời điểm chụp */
        public readonly ?float $capturedLat,
        public readonly ?float $capturedLng,
        /** Lý do bỏ qua (A3/A6) */
        public readonly ?string $skipReason = null,
    ) {}
}
