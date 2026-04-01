<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests\Auth;

use App\Modules\User\Model\Enums\UserOtpType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'type'  => ['required', 'integer', Rule::enum(UserOtpType::class)],
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
