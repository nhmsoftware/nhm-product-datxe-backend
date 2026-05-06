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

final class AdminCreateVoucherRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|unique:vouchers,code|max:50',
            'name' => 'required|string|max:100',
            'service_type' => ['required', new Enum(VoucherServiceType::class)],
            'discount_type' => ['required', new Enum(VoucherDiscountType::class)],
            'discount_value' => [
                'required', 
                'numeric', 
                'gt:0',
                function ($attribute, $value, $fail) {
                    if ($this->input('discount_type') === VoucherDiscountType::PERCENT->value && $value > 100) {
                        $fail('Phần trăm giảm giá không được lớn hơn 100.');
                    }
                }
            ],
            'min_order_amount' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from|after:today',
            'total_usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu tạo voucher không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
