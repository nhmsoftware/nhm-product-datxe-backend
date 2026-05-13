<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\ConfigureCommissionRequest;
use App\Modules\Finance\Model\Enums\CommissionScope;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use Illuminate\Support\Carbon;

final class ConfigureCommissionDTO
{
    public function __construct(
        public readonly string                $name,
        public readonly \App\Modules\Finance\Model\Enums\CommissionTargetType $targetType,
        public readonly CommissionServiceType $serviceType,
        public readonly CommissionScope       $scope,
        public readonly ?string               $areaId,
        public readonly float                 $commissionRate,
        public readonly ?float                $minCommission,
        public readonly ?float                $maxCommission,
        public readonly bool                  $isActive,
        public readonly Carbon                $effectiveFrom,
        public readonly ?Carbon               $effectiveTo,
    ) {}

    public static function fromRequest(ConfigureCommissionRequest $request): self
    {
        return new self(
            name:           $request->string('name')->toString(),
            targetType:     \App\Modules\Finance\Model\Enums\CommissionTargetType::from((int) $request->input('target_type')),
            serviceType:    CommissionServiceType::from((int) $request->input('service_type')),
            scope:          CommissionScope::from((int) $request->input('scope')),
            areaId:         $request->input('area_id'),
            commissionRate: (float) $request->input('commission_rate'),
            minCommission:  $request->has('min_commission') ? (float) $request->input('min_commission') : null,
            maxCommission:  $request->has('max_commission') ? (float) $request->input('max_commission') : null,
            isActive:       $request->boolean('is_active', true),
            effectiveFrom:  Carbon::parse($request->input('effective_from')),
            effectiveTo:    $request->input('effective_to') ? Carbon::parse($request->input('effective_to')) : null,
        );
    }
}
