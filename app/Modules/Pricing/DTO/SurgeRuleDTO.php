<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use Illuminate\Http\Request;

final class SurgeRuleDTO
{
    public function __construct(
        public readonly int     $vehicleTypeId,
        public readonly array   $conditions,
        public readonly float   $multiplier,
        public readonly ?string $startTime,
        public readonly ?string $endTime,
        public readonly ?string $areaId,
        public readonly bool    $isActive = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vehicleTypeId: (int) $request->input('vehicle_type_id'),
            conditions:  $request->input('conditions'),
            multiplier:  (float) $request->input('multiplier'),
            startTime:   $request->input('start_time'),
            endTime:     $request->input('end_time'),
            areaId:      $request->input('area_id'),
            isActive:    $request->boolean('is_active', true),
        );
    }

    public function toArray(): array
    {
        return [
            'vehicle_type_id' => $this->vehicleTypeId,
            'conditions'   => $this->conditions,
            'multiplier'   => $this->multiplier,
            'start_time'   => $this->startTime,
            'end_time'     => $this->endTime,
            'area_id'      => $this->areaId,
            'is_active'    => $this->isActive,
        ];
    }
}
