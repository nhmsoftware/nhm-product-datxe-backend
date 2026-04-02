<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests;

use App\Modules\User\Model\Enums\UserOtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^0[0-9]{9}$/'],
            'type'  => ['required', 'integer', Rule::in(
                array_column(UserOtpType::cases(), 'value')
            )],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex'    => 'Số điện thoại không đúng định dạng.',
            'type.required'  => 'Loại OTP không được để trống.',
            'type.enum'        => 'Loại OTP không hợp lệ.',
        ];
    }
}
