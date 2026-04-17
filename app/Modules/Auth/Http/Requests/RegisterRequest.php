<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{

    /**
     * Xác định xem người dùng có được phép thực hiện yêu cầu này hay không.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Định nghĩa quy tắc xác thực cho yêu cầu này.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'phone'        => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'otp'          => ['required', 'string', 'size:6'],
            'full_name'    => ['nullable', 'string', 'max:100'],
            'password'     => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/',
            ],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'role'         => ['sometimes', 'integer', 'in:' . Rule::enum(UserRole::class)],
            'device_id'    => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:500'],
            'device_type'  => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Định nghĩa các thông báo xác thực cho yêu cầu này.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'phone.required'    => 'Số điện thoại không được để trống.',
            'phone.regex'       => 'Số điện thoại không đúng định dạng Việt Nam.',
            'otp.required'      => 'Mã OTP không được để trống.',
            'otp.size'          => 'Mã OTP phải bao gồm 6 chữ số.',
            'full_name.required' => 'Tên không được để trống.',
            'full_name.max'      => 'Tên không được vượt quá 100 ký tự.',
            'role.required'      => 'Vai lòng chọn vai trò.',
            'role.in'            => 'Vai trò không hợp lệ.',
            'role.sometimes'     => 'Vai trò không được để trống.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min'      => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed'=> 'Xác nhận mật khẩu không khớp.',
            'password.regex'    => 'Mật khẩu phải chứa ít nhất một chữ hoa, một chữ thường, một số và một ký tự đặc biệt.',
        ];
    }
}
