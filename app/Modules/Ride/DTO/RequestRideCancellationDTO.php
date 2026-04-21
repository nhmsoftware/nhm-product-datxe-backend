<?php

declare(strict_types=1);

namespace App\Modules\Ride\DTO;

final readonly class RequestRideCancellationDTO
{
    public function __construct(
        public string $rideId,
        public string $customerId,
        public ?string $reason = null,
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            customerId: (string) $request->user()->id,
            reason: $request->input('reason')
        );
    }
}
