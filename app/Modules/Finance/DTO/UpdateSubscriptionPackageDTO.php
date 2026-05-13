<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Illuminate\Http\Request;

final class UpdateSubscriptionPackageDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $packageType,
        public readonly float   $price,
        public readonly int     $durationDays,
        public readonly float   $serviceFeeReductionPercent,
        public readonly ?string $description,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:                       $request->string('name')->toString(),
            packageType:                $request->string('package_type')->toString(),
            price:                      (float) $request->input('price'),
            durationDays:               (int)   $request->input('duration_days'),
            serviceFeeReductionPercent: (float) $request->input('service_fee_reduction_percent', 100),
            description:                $request->input('description'),
        );
    }
}
