<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class InitiateTopUpRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Số tiền nạp: validate min/max theo từng phương thức sẽ được xử lý trong Service
            'amount'              => 'required|numeric|min:1000|max:500000000',
            // Code phương thức thanh toán từ bảng payment_methods (validate Active/tồn tại trong Service)
            'payment_method_code' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'              => 'Vui lòng nhập số tiền nạp.',
            'amount.numeric'               => 'Số tiền nạp phải là số.',
            'amount.min'                   => 'Số tiền nạp phải lớn hơn 0.',
            'payment_method_code.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method_code.string'   => 'Phương thức thanh toán không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}

