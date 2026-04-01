<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Modules\User\Domain\Enums\UserRole;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'        => ['required', 'string', 'regex:/^0[3-9]\d{8}$/'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'full_name'    => ['required', 'string', 'max:100'],
            'role'         => ['sometimes', 'integer', 'in:' . implode(',', [
                UserRole::Customer->value,
                // Chỉ cho phép đăng ký Customer qua API này.
                // Driver/Merchant đi qua luồng KYC riêng.
            ])],
            // Device info (optional)
            'device_id'    => ['nullable', 'string', 'max:255'],
            'device_token' => ['nullable', 'string', 'max:500'],
            'device_type'  => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'    => 'Số điện thoại không được để trống.',
            'phone.regex'       => 'Số điện thoại không đúng định dạng Việt Nam.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min'      => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed'=> 'Xác nhận mật khẩu không khớp.',
            'full_name.required'=> 'Họ tên không được để trống.',
        ];
    }
}
