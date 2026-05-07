<?php

declare(strict_types=1);

namespace App\Modules\Ride\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * UC-38: Sự kiện xảy ra khi Driver đã xác nhận giao hàng (chụp ảnh bằng chứng).
 */
final class DeliveryProofCaptured
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $rideId,
        public readonly string $driverId,
        public readonly string $customerId,
        public readonly ?string $photoUrl,
        public readonly string $capturedAt,
        public readonly ?float $capturedLat,
        public readonly ?float $capturedLng,
        public readonly ?string $skipReason = null,
    ) {}
}
