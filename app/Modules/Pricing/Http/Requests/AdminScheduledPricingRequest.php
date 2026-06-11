<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class AdminScheduledPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pre_book_surcharge'      => 'nullable|numeric|min:0',
            'night_surcharge'         => 'nullable|numeric|min:0',
            'holiday_surcharge'       => 'nullable|numeric|min:0',
            'waiting_surcharge'       => 'nullable|numeric|min:0',
            'toll_surcharge'          => 'nullable|numeric|min:0',
            'dispatch_mode'           => 'required|integer|in:1,2',
            'rules'                   => 'nullable|array',
            'rules.*.service_type'    => 'required|integer|in:6,7',
            'rules.*.ride_mode'       => 'required|string',
            'rules.*.vehicle_type_id' => 'nullable|integer|min:1',
            'rules.*.vehicle_type'    => 'nullable|integer|min:1',
            'rules.*.airport_id'      => 'nullable|string',
            'rules.*.ranges'          => 'required|array|min:1',
            'rules.*.ranges.*.start_km'=> 'required|numeric|min:0',
            'rules.*.ranges.*.end_km'  => 'required|numeric|gt:rules.*.ranges.*.start_km',
            'rules.*.ranges.*.price'   => 'required|numeric|min:0',
            'rules.*.ranges.*.unit'    => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'rules.*.ranges.*.end_km.gt' => 'Khoảng KM không hợp lệ. start_km phải nhỏ hơn end_km.',
            'dispatch_mode.in'           => 'Chế độ phân phối không hợp lệ.',
            'rules.*.ranges.min'         => 'Vui lòng nhập giá cho khoảng KM.',
            'rules.*.vehicle_type_id.min' => 'Loại xe không hợp lệ.',
            'rules.*.vehicle_type.min'    => 'Loại xe không hợp lệ.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $rules = collect($this->input('rules', []))
            ->map(function ($rule) {
                if (!is_array($rule)) {
                    return $rule;
                }

                if (
                    (!isset($rule['vehicle_type_id']) || $rule['vehicle_type_id'] === null || $rule['vehicle_type_id'] === '')
                    && isset($rule['vehicle_type'])
                ) {
                    $rule['vehicle_type_id'] = $rule['vehicle_type'];
                }

                return $rule;
            })
            ->all();

        $this->merge([
            'rules' => $rules,
        ]);
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $rules = $this->input('rules', []);
            foreach ($rules as $ruleIndex => $rule) {
                if (empty($rule['vehicle_type_id'])) {
                    $validator->errors()->add(
                        "rules.{$ruleIndex}.vehicle_type_id",
                        'Vui lòng chọn loại xe.'
                    );
                }

                $ranges = $rule['ranges'] ?? [];
                
                // Sort ranges by start_km
                usort($ranges, fn($a, $b) => $a['start_km'] <=> $b['start_km']);

                $previousEnd = null;
                foreach ($ranges as $rangeIndex => $range) {
                    $start = (float)($range['start_km'] ?? 0);
                    $end = (float)($range['end_km'] ?? 0);

                    if ($previousEnd !== null && $start < $previousEnd) {
                        $validator->errors()->add(
                            "rules.{$ruleIndex}.ranges",
                            "Khoảng KM này bị trùng với khoảng KM khác."
                        );
                        break;
                    }
                    $previousEnd = $end;
                }
            }
        });
    }
}
