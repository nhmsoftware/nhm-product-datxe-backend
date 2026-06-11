<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Repositories;

use App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface;
use App\Modules\Pricing\Model\ScheduledPricingSurcharge;
use App\Modules\Pricing\Model\ScheduledPricingRule;
use Illuminate\Support\Facades\DB;

final class ScheduledPricingRepository implements ScheduledPricingRepositoryInterface
{
    public function getCurrentConfig(): array
    {
        $surcharge = ScheduledPricingSurcharge::where('is_active', true)->first();
        $rules = ScheduledPricingRule::with('ranges')->where('is_active', true)->get();

        return [
            'surcharges' => $surcharge ? $surcharge->toArray() : null,
            'rules'      => $rules->toArray(),
        ];
    }

    public function saveConfig(array $surchargeData, array $rulesData): array
    {
        return DB::transaction(function () use ($surchargeData, $rulesData) {
            // Vô hiệu hóa cấu hình cũ
            ScheduledPricingSurcharge::where('is_active', true)->update(['is_active' => false]);
            ScheduledPricingRule::where('is_active', true)->update(['is_active' => false]);
            // Không cần update ranges vì ranges cũ sẽ đi theo rules cũ

            // Tạo cấu hình phụ phí mới
            $surchargeData['is_active'] = true;
            $surcharge = ScheduledPricingSurcharge::create($surchargeData);

            $rules = [];
            foreach ($rulesData as $ruleData) {
                $rangesData = $ruleData['ranges'] ?? [];
                unset($ruleData['ranges']);

                $ruleData['is_active'] = true;
                $rule = ScheduledPricingRule::create($ruleData);

                $ruleRanges = [];
                foreach ($rangesData as $rangeData) {
                    $rangeData['is_active'] = true;
                    $ruleRanges[] = $rule->ranges()->create($rangeData)->toArray();
                }

                $ruleArray = $rule->toArray();
                $ruleArray['ranges'] = $ruleRanges;
                $rules[] = $ruleArray;
            }

            return [
                'surcharges' => $surcharge->toArray(),
                'rules'      => $rules,
            ];
        });
    }

    public function findMatchingRule(
        int $serviceType,
        string $rideMode,
        int $vehicleTypeId,
        ?string $airportId = null
    ): ?ScheduledPricingRule {
        $query = ScheduledPricingRule::with('ranges')
            ->where('is_active', true)
            ->where('service_type', $serviceType)
            ->where('ride_mode', $rideMode)
            ->where('vehicle_type_id', $vehicleTypeId);

        if ($airportId !== null && $airportId !== '') {
            $query->where(function ($inner) use ($airportId) {
                $inner->where('airport_id', $airportId)
                    ->orWhereNull('airport_id')
                    ->orWhere('airport_id', '');
            })->orderByRaw("CASE WHEN airport_id = ? THEN 0 ELSE 1 END", [$airportId]);
        } else {
            $query->where(function ($inner) {
                $inner->whereNull('airport_id')
                    ->orWhere('airport_id', '');
            });
        }

        /** @var ScheduledPricingRule|null */
        return $query->first();
    }
}
