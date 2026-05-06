<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminCancellationConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ride_type'                 => 'required|integer|in:1,2,3',
            'min_minutes_before_pickup' => 'required|integer|min:0',
            'fee_type'                  => 'required|integer|in:1,2',
            'fee_value'                 => 'required|numeric|min:0',
            'is_active'                 => 'boolean',
            'description'               => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'ride_type.required'                 => 'Vui lòng chọn loại chuyến xe.',
            'min_minutes_before_pickup.required' => 'Vui lòng nhập thời gian giới hạn.',
            'fee_type.required'                  => 'Vui lòng chọn loại phí.',
            'fee_value.required'                 => 'Vui lòng nhập giá trị phí.',
        ];
    }
}
