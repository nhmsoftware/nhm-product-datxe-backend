<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'    => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'otp'      => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'     => 'Số điện thoại không được để trống.',
            'phone.regex'        => 'Số điện thoại không đúng định dạng.',
            'otp.required'       => 'Mã OTP không được để trống.',
            'otp.size'           => 'Mã OTP phải bao gồm 6 chữ số.',
            'password.required'  => 'Mật khẩu không được để trống.',
            'password.min'       => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ cái và số.',
            'password.regex'     => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ cái và số.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ];
    }
}
