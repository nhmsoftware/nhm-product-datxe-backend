<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nhận các quy tắc xác thực áp dụng cho yêu cầu.
     * @return array
     */
    public function rules(): array
    {
        return [
            'phone'        => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'password'     => ['required', 'string'],
            'device_id'    => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:500'],
            'device_type'  => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Nhận các thông báo xác thực áp dụng cho yêu cầu.
     * @return array
     */
    public function messages(): array
    {
        return [
            'phone.required'    => 'Vui lòng nhập số điện thoại.',
            'phone.regex'       => 'Số điện thoại không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ];
    }
}
