<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use App\Modules\Finance\Model\Enums\VoucherDiscountType;
use App\Modules\Finance\Model\Enums\VoucherServiceType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

final class AdminUpdateVoucherRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'code' => 'nullable|string|max:50|unique:vouchers,code,' . $id,
            'name' => 'nullable|string|max:100',
            'service_type' => ['nullable', new Enum(VoucherServiceType::class)],
            'discount_type' => ['nullable', new Enum(VoucherDiscountType::class)],
            'discount_value' => [
                'nullable', 
                'numeric', 
                'gt:0',
                function ($attribute, $value, $fail) {
                    $type = $this->input('discount_type');
                    if ($type === VoucherDiscountType::PERCENT->value && $value > 100) {
                        $fail('Phần trăm giảm giá không được lớn hơn 100.');
                    }
                }
            ],
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from|after:today',
            'total_usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu cập nhật voucher không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
