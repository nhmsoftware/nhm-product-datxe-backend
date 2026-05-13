<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class AdminCreateSubscriptionPackageRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                          => 'required|string|max:100|unique:subscription_packages,name',
            'package_type'                  => 'required|string|in:daily,weekly,monthly',
            'price'                         => 'required|numeric|gt:0',
            'duration_days'                 => 'required|integer|gt:0',
            'service_fee_reduction_percent' => 'nullable|numeric|min:0|max:100',
            'description'                   => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'      => 'Gói thuê bao này đã tồn tại.',
            'price.gt'         => 'Giá gói thuê bao không hợp lệ.',
            'duration_days.gt' => 'Thời hạn gói thuê bao không hợp lệ.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Vui lòng nhập đầy đủ thông tin gói thuê bao.', $validator->errors()->toArray(), 400)
        );
    }
}
