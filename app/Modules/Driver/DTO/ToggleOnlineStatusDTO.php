<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

use App\Modules\Driver\Http\Requests\ToggleOnlineStatusRequest;

final class ToggleOnlineStatusDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly bool $isOnline,
        public readonly ?float $currentLat = null,
        public readonly ?float $currentLng = null,
    ) {}

    public static function fromRequest(ToggleOnlineStatusRequest $request): self
    {
        return new self(
            userId:     (string) $request->user()->id,
            isOnline:   $request->boolean('is_online'),
            currentLat: $request->has('current_lat') ? (float) $request->input('current_lat') : null,
            currentLng: $request->has('current_lng') ? (float) $request->input('current_lng') : null,
        );
    }
}
