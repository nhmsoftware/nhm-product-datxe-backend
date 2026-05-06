<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\DTO;

use Illuminate\Http\Request;

final class UpdateCancellationConfigDTO
{
    public function __construct(
        public readonly ?int $rideType = null,
        public readonly ?int $minMinutesBeforePickup = null,
        public readonly ?int $feeType = null,
        public readonly ?float $feeValue = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $description = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            rideType:               $request->has('ride_type') ? (int) $request->input('ride_type') : null,
            minMinutesBeforePickup: $request->has('min_minutes_before_pickup') ? (int) $request->input('min_minutes_before_pickup') : null,
            feeType:                $request->has('fee_type') ? (int) $request->input('fee_type') : null,
            feeValue:               $request->has('fee_value') ? (float) $request->input('fee_value') : null,
            isActive:               $request->has('is_active') ? $request->boolean('is_active') : null,
            description:            $request->input('description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'ride_type'                 => $this->rideType,
            'min_minutes_before_pickup' => $this->minMinutesBeforePickup,
            'fee_type'                  => $this->feeType,
            'fee_value'                 => $this->feeValue,
            'is_active'                 => $this->isActive,
            'description'               => $this->description,
        ], fn($value) => $value !== null);
    }
}
