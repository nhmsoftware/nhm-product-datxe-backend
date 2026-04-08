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
            'password'     => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'otp'          => ['required', 'string', 'regex:/^\d{6}$/'],
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
            'phone.string'      => 'Số điện thoại phải là chuỗi.',
            'phone.max'         => 'Số điện thoại không được quá 11 kí tự.',
            'phone.min'         => 'Số điện thoại phải có 11 kí tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string'   => 'Mật khẩu phải là chuỗi.',
            'password.max'      => 'Mật khẩu không được quá 255 kí tự.',
            'password.min'      => 'Mật khẩu phải có 8 kí tự.',
            'password.confirmed' => 'Mật khẩu không khớp với xác nhận mật khẩu.',
            'password.same'       => 'Mật khẩu không khớp với xác nhận mật khẩu.',
            'device_id.max' => 'Device ID không được quá 255 kí tự.',
            'device_token.max' => 'Device Token không được quá 500 kí tự.',
            'device_type.max' => 'Device Type không được quá 50 kí tự.',
            'device_type.string' => 'Device Type phải là chuỗi.',
            'device_type.min' => 'Device Type phải có 1 kí tự.',
            'device_type.required' => 'Vui lòng chọn Device Type.',
            'device_type.in' => 'Device Type không hợp lệ.',
            'device_type.exists' => 'Device Type không tồn tại.',
            'device_type.unique' => 'Device Type đã được sử dụng.'
        ];
    }
}
