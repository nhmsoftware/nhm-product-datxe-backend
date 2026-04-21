<?php

declare(strict_types=1);

namespace App\Modules\Communication\DTO;

use App\Modules\Communication\Http\Requests\SendMessageRequest;

final class SendMessageDTO
{
    public function __construct(
        public readonly string $rideId,
        public readonly string $senderId,
        public readonly string $message,
    ) {}

    public static function fromRequest(SendMessageRequest $request, string $rideId): self
    {
        return new self(
            rideId:   $rideId,
            senderId: (string) $request->user()->id,
            message:  $request->string('message')->toString(),
        );
    }
}
