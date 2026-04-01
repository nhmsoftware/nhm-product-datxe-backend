<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Domain\Enums\UserOtpType;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $validTypes = implode(',', array_column(UserOtpType::cases(), 'value'));

        return [
            'phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'type'  => ['required', 'integer', "in:{$validTypes}"],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Số điện thoại không được để trống.',
            'phone.regex'    => 'Số điện thoại không đúng định dạng.',
            'type.required'  => 'Loại OTP không được để trống.',
            'type.in'        => 'Loại OTP không hợp lệ.',
        ];
    }
}
