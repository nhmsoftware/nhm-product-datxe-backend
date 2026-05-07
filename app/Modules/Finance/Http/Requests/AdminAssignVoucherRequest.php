<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Core\Traits\HandleApi;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class AdminAssignVoucherRequest extends FormRequest
{
    use HandleApi;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'voucher_id' => 'required|exists:vouchers,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->sendValidation('Dữ liệu gán voucher không hợp lệ.', $validator->errors()->toArray(), 400)
        );
    }
}
