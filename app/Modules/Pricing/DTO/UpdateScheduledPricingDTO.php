<?php

declare(strict_types=1);

namespace App\Modules\Pricing\DTO;

use Illuminate\Http\Request;

final class UpdateScheduledPricingDTO
{
    public function __construct(
        public readonly float $preBookSurcharge,
        public readonly float $nightSurcharge,
        public readonly float $holidaySurcharge,
        public readonly float $waitingSurcharge,
        public readonly float $tollSurcharge,
        public readonly int $dispatchMode,
        /** @var array<array> */
        public readonly array $rules,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $rules = collect((array) $request->input('rules', []))
            ->map(function ($rule) {
                if (!is_array($rule)) {
                    return $rule;
                }

                $rule['vehicle_type_id'] = (int) ($rule['vehicle_type_id'] ?? $rule['vehicle_type'] ?? 0);

                return $rule;
            })
            ->all();

        return new self(
            preBookSurcharge: (float) $request->input('pre_book_surcharge', 0),
            nightSurcharge:   (float) $request->input('night_surcharge', 0),
            holidaySurcharge: (float) $request->input('holiday_surcharge', 0),
            waitingSurcharge: (float) $request->input('waiting_surcharge', 0),
            tollSurcharge:    (float) $request->input('toll_surcharge', 0),
            dispatchMode:     (int) $request->input('dispatch_mode', 1),
            rules:            $rules,
        );
    }

    public function toSurchargeArray(): array
    {
        return [
            'pre_book_surcharge' => $this->preBookSurcharge,
            'night_surcharge'    => $this->nightSurcharge,
            'holiday_surcharge'  => $this->holidaySurcharge,
            'waiting_surcharge'  => $this->waitingSurcharge,
            'toll_surcharge'     => $this->tollSurcharge,
        ];
    }
}
