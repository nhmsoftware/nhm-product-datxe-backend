<?php

declare(strict_types=1);

namespace App\Modules\RiskManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminPenaltyRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:255',
            'violation_type'      => 'required|integer',
            'applicable_role'     => 'required|integer',
            'violation_threshold' => 'required|integer|min:1',
            'penalty_type'        => 'required|integer',
            'penalty_duration'    => 'nullable|integer|min:0',
            'monetary_amount'     => 'nullable|numeric|min:0',
            'reputation_points'   => 'nullable|integer|min:0',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                => 'Vui lòng nhập tên quy tắc.',
            'violation_threshold.min'      => 'Số lần vi phạm không hợp lệ.',
            'violation_type.required'      => 'Vui lòng chọn loại vi phạm.',
            'applicable_role.required'     => 'Vui lòng chọn đối tượng áp dụng.',
            'penalty_type.required'        => 'Vui lòng chọn hình thức phạt.',
        ];
    }
}
