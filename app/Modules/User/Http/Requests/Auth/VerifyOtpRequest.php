<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Domain\Enums\UserOtpType;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $validTypes = implode(',', array_column(UserOtpType::cases(), 'value'));

        return [
            'phone' => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'otp'   => ['required', 'string', 'digits:6'],
            'type'  => ['required', 'integer', "in:{$validTypes}"],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'Mã OTP không được để trống.',
            'otp.digits'   => 'Mã OTP phải gồm 6 chữ số.',
        ];
    }
}
