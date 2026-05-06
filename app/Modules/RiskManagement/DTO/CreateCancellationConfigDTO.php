<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\DTO;

use Illuminate\Http\Request;

final class CreateCancellationConfigDTO
{
    public function __construct(
        public readonly int $rideType,
        public readonly int $minMinutesBeforePickup,
        public readonly int $feeType,
        public readonly float $feeValue,
        public readonly bool $isActive = true,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideType:               (int) $request->input('ride_type'),
            minMinutesBeforePickup: (int) $request->input('min_minutes_before_pickup', 0),
            feeType:                (int) $request->input('fee_type'),
            feeValue:               (float) $request->input('fee_value', 0),
            isActive:               $request->boolean('is_active', true),
            description:            $request->input('description'),
        );
    }

    public function toArray(): array
    {
        return [
            'ride_type'                 => $this->rideType,
            'min_minutes_before_pickup' => $this->minMinutesBeforePickup,
            'fee_type'                  => $this->feeType,
            'fee_value'                 => $this->feeValue,
            'is_active'                 => $this->isActive,
            'description'               => $this->description,
        ];
    }
}
