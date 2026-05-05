<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;

final class SetSurgePricingRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_type' => ['required', 'integer'],
            'conditions'   => ['required', 'array', 'min:1'], // Alternative Flow A2
            'multiplier'   => ['required', 'numeric', 'gt:1'], // Alternative Flow A3
            'start_time'   => ['nullable', 'date_format:H:i'],
            'end_time'     => ['nullable', 'date_format:H:i', 'after:start_time'], // Alternative Flow A4
            'area_id'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_type.required' => 'Vui lòng chọn loại xe.',
            'conditions.required'   => 'Vui lòng chọn ít nhất một điều kiện áp dụng.',
            'conditions.min'        => 'Vui lòng chọn ít nhất một điều kiện áp dụng.',
            'multiplier.required'   => 'Hệ số tăng giá không hợp lệ.',
            'multiplier.numeric'    => 'Hệ số tăng giá không hợp lệ.',
            'multiplier.gt'         => 'Hệ số tăng giá không hợp lệ.',
            'end_time.after'        => 'Khung thời gian áp dụng không hợp lệ.',
            'start_time.date_format' => 'Khung thời gian áp dụng không hợp lệ.',
            'end_time.date_format'   => 'Khung thời gian áp dụng không hợp lệ.',
        ];
    }
}
