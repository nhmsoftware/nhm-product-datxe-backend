<?php

declare(strict_types=1);

namespace App\Modules\Driver\DTO;

final readonly class ApproveRegistrationDTO
{
    public function __construct(
        public int $applicationId,
        public ?int $driverGroupId = null,
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            applicationId: (int) $request->route('id'),
            driverGroupId: $request->has('driver_group_id') ? (int) $request->input('driver_group_id') : null
        );
    }
}
