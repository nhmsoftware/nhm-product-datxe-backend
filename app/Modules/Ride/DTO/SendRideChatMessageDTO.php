<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

use App\Modules\Ride\Http\Requests\SendRideChatMessageRequest;

final class SendRideChatMessageDTO
{
    public function __construct(
        public readonly int $rideId,
        public readonly int $actorId,
        public readonly string $message,
    ) {
    }

    public static function fromRequest(SendRideChatMessageRequest $request, int $rideId): self
    {
        return new self(
            rideId: $rideId,
            actorId: (int) $request->user()->id,
            message: $request->string('message')->toString(),
        );
    }
}
