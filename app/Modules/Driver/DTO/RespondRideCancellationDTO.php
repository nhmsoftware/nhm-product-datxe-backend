<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

final readonly class RespondRideCancellationDTO
{
    public function __construct(
        public string $rideId,
        public string $driverId,
        public bool $isApproved,
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            rideId: (string) $request->route('rideId'),
            driverId: (string) $request->user()->id,
            isApproved: (bool) $request->input('agreement')
        );
    }
}
