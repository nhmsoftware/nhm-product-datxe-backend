<?php

declare(strict_types=1);

namespace App\Modules\Notification\DTO;

use Illuminate\Http\Request;

final class UpdateDeviceTokenDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $deviceId,
        public readonly string $deviceToken,
        public readonly ?string $deviceType = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: (string) $request->user()->id,
            deviceId: (string) $request->input('device_id'),
            deviceToken: (string) $request->input('device_token'),
            deviceType: $request->input('device_type'),
        );
    }
}
