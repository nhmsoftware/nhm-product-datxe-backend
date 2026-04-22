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
            'amount'         => 'required|numeric|min:10000|max:10000000',
            'payment_method' => 'required|string|in:momo,vnpay,card',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Số tiền nạp tối thiểu là 10.000đ.',
            'amount.max' => 'Số tiền nạp tối đa là 10.000.000đ.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
