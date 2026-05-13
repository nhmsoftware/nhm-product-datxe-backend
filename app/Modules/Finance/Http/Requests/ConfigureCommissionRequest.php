<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use App\Modules\Finance\Model\Enums\CommissionScope;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class ConfigureCommissionRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true; // Phân quyền thực tế qua middleware hoặc Gate
    }

    public function rules(): array
    {
        return [
            'name'            => ['nullable', 'string', 'max:255'],
            'target_type'     => ['required', new Enum(\App\Modules\Finance\Model\Enums\CommissionTargetType::class)],
            'service_type'    => ['required', new Enum(CommissionServiceType::class)],
            'scope'           => ['required', new Enum(CommissionScope::class)],
            'area_id'         => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_commission'  => ['nullable', 'numeric', 'min:0'],
            'max_commission'  => ['nullable', 'numeric', 'min:0', 'gte:min_commission'],
            'is_active'       => ['nullable', 'boolean'],
            'effective_from'  => ['required', 'date'],
            'effective_to'    => ['nullable', 'date', 'after:effective_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'commission_rate.min' => 'Tỷ lệ hoa hồng không hợp lệ.',
            'commission_rate.max' => 'Tỷ lệ hoa hồng không hợp lệ.',
            'max_commission.gte'  => 'Giá trị tối đa phải lớn hơn hoặc bằng giá trị tối thiểu.',
            'effective_to.after'  => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ];
    }
}
